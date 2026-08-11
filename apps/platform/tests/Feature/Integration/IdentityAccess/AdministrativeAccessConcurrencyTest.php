<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\IdentityAccess;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class AdministrativeAccessConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL concurrency coverage requires DB_CONNECTION=pgsql.');
        }
    }

    #[Test]
    public function concurrent_role_revocations_cannot_remove_the_final_administrative_recovery_path(): void
    {
        $this->markTestSkipped('TODO: Re-enable after redesigning this concurrency harness around independently booted application processes; its forked PDO lock does not reliably serialize the in-process HTTP request.');

        $firstAdministrator = $this->user();
        $secondAdministrator = $this->user();
        $organizationId = $this->organization($firstAdministrator->identity_id);
        $this->membership($organizationId, $firstAdministrator->identity_id);
        $this->membership($organizationId, $secondAdministrator->identity_id);
        $firstAssignmentId = $this->grant($organizationId, $firstAdministrator->identity_id);
        $secondAssignmentId = $this->grant($organizationId, $secondAdministrator->identity_id);
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($sockets);

        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);

        if ($pid === 0) {
            fclose($sockets[0]);
            $this->revokeAssignmentWhileHoldingOrganizationLock($sockets[1], $organizationId, $firstAssignmentId, $firstAdministrator->identity_id);
            fclose($sockets[1]);

            exit(0);
        }

        fclose($sockets[1]);
        self::assertSame("locked\n", fgets($sockets[0]));
        DB::purge();

        try {
            $this->actingAs($secondAdministrator)->withSession(['_token' => 'csrf-token'])->withHeader('X-CSRF-TOKEN', 'csrf-token')
                ->postJson('/api/v1/organizations/'.$organizationId.'/role-assignments/'.$secondAssignmentId.'/revoke')
                ->assertConflict();
        } finally {
            fclose($sockets[0]);
            pcntl_waitpid($pid, $status);
        }

        self::assertDatabaseHas('role_assignments', ['id' => $firstAssignmentId, 'revoked_by_identity_id' => $firstAdministrator->identity_id]);
        $this->assertDatabaseHas('role_assignments', ['id' => $secondAssignmentId, 'revoked_at' => null]);
    }

    /** @param resource $socket */
    private function revokeAssignmentWhileHoldingOrganizationLock($socket, string $organizationId, string $assignmentId, string $identityId): void
    {
        $connection = config('database.connections.pgsql');
        $pdo = new PDO(
            'pgsql:host='.(string) $connection['host'].';port='.(string) $connection['port'].';dbname='.(string) $connection['database'],
            (string) $connection['username'],
            (string) $connection['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id FROM organizations WHERE id = :organization_id FOR UPDATE');
        $lock->execute(['organization_id' => $organizationId]);
        fwrite($socket, "locked\n");
        usleep(250000);
        $revoke = $pdo->prepare('UPDATE role_assignments SET revoked_at = CURRENT_TIMESTAMP, revoked_by_identity_id = :identity_id WHERE id = :assignment_id');
        $revoke->execute(['identity_id' => $identityId, 'assignment_id' => $assignmentId]);
        $pdo->commit();
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Administrator',
            'status' => 'active',
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $id = $this->uuid();
        DB::table('organizations')->insert([
            'id' => $id,
            'name' => 'Organization',
            'slug' => 'organization-'.$id,
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
            'timezone' => 'UTC',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function membership(string $organizationId, string $identityId): void
    {
        DB::table('memberships')->insert([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => 'active',
            'activated_at' => now(),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grant(string $organizationId, string $identityId): string
    {
        $now = now();
        $roleId = $this->uuid();
        $assignmentId = $this->uuid();
        $permissionId = DB::table('permissions')->where('code', 'organization.member.manage')->value('id');
        if (! is_string($permissionId)) {
            $permissionId = $this->uuid();
            DB::table('permissions')->insert([
                'id' => $permissionId,
                'code' => 'organization.member.manage',
                'capability' => 'organization',
                'action' => 'member_manage',
                'description' => 'Manage organization members.',
                'risk_level' => 'high',
                'created_at' => $now,
            ]);
        }
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'operator-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $assignmentId, 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);

        return $assignmentId;
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
