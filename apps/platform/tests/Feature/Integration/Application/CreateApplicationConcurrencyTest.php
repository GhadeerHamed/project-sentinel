<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Application;

use App\Infrastructure\Authentication\IdentityUserAuthenticatable;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Application\Application\Command\CreateApplicationCommand;
use Sentinel\Application\Application\Handler\CreateApplicationHandler;
use Sentinel\Application\Application\Policy\ApplicationCreationPolicy;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Tests\TestCase;
use Throwable;

final class CreateApplicationConcurrencyTest extends TestCase
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
    public function concurrent_requests_with_one_idempotency_key_create_only_one_application(): void
    {
        $user = $this->user();
        $organizationId = $this->organization($user->identity_id);
        $projectId = $this->project($organizationId, $user->identity_id);
        $this->membership($organizationId, $user->identity_id);
        $this->grantApplicationCreate($organizationId, $user->identity_id);
        $firstPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $secondPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($firstPair);
        self::assertIsArray($secondPair);

        $firstPid = $this->forkApplicationCreation($firstPair, $organizationId, $projectId, $user->identity_id);
        $secondPid = $this->forkApplicationCreation($secondPair, $organizationId, $projectId, $user->identity_id);
        fclose($firstPair[1]);
        fclose($secondPair[1]);

        fwrite($firstPair[0], "go\n");
        fwrite($secondPair[0], "go\n");
        $firstResult = fgets($firstPair[0]);
        $secondResult = fgets($secondPair[0]);
        fclose($firstPair[0]);
        fclose($secondPair[0]);
        pcntl_waitpid($firstPid, $firstStatus);
        pcntl_waitpid($secondPid, $secondStatus);
        DB::purge();

        self::assertSame(0, pcntl_wexitstatus($firstStatus));
        self::assertSame(0, pcntl_wexitstatus($secondStatus));
        self::assertIsString($firstResult);
        self::assertIsString($secondResult);
        self::assertStringStartsWith('ok:', $firstResult);
        self::assertStringStartsWith('ok:', $secondResult);
        self::assertSame(trim($firstResult), trim($secondResult));
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        $this->assertDatabaseCount('audit_records', 1);
    }

    /** @param array{resource, resource} $pair */
    private function forkApplicationCreation(array $pair, string $organizationId, string $projectId, string $identityId): int
    {
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);

        if ($pid !== 0) {
            return $pid;
        }

        fclose($pair[0]);

        try {
            fgets($pair[1]);
            DB::purge();
            $result = $this->handler($organizationId, $identityId)->handle(
                CreateApplicationCommand::fromValues(
                    projectId: $projectId,
                    name: 'Concurrent Checkout API',
                    slug: 'concurrent-checkout-api',
                    description: null,
                    criticality: 'high',
                    idempotencyKey: 'concurrent-application-create',
                ),
            );
            fwrite($pair[1], 'ok:'.$result->applicationId()->value()."\n");
            fclose($pair[1]);

            exit(0);
        } catch (Throwable $exception) {
            fwrite($pair[1], 'error:'.$exception::class.':'.$exception->getMessage()."\n");
            fclose($pair[1]);

            exit(1);
        }
    }

    private function handler(string $organizationId, string $identityId): CreateApplicationHandler
    {
        $tenantContext = TenantContext::fromValues(
            organizationId: $organizationId,
            identityId: $identityId,
            identityType: 'user',
            correlationId: $this->app->make(UuidV7Generator::class)->generate(),
        );

        return new CreateApplicationHandler(
            applications: $this->app->make(ApplicationRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applicationCreationPolicy: $this->app->make(ApplicationCreationPolicy::class),
            tenantContext: $tenantContext,
            idempotentOperation: new IdempotentOperation(
                $this->app->make(TransactionManager::class),
                $this->app->make(IdempotencyRepository::class),
                $this->app->make(UuidV7Generator::class),
                $this->app->make(Clock::class),
            ),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            operationRecorder: new TenantScopedOperationRecorder(
                $tenantContext,
                $this->app->make(UuidV7Generator::class),
                $this->app->make(OutboxMessageStore::class),
                $this->app->make(AuditRecorder::class),
            ),
        );
    }

    private function user(): IdentityUserAuthenticatable
    {
        $user = new IdentityUserAuthenticatable;
        $user->forceFill([
            'identity_id' => $this->uuid(),
            'email' => $this->uuid().'@example.test',
            'display_name' => 'Application Creator',
            'status' => 'active',
            'version' => 1,
        ]);
        $user->save();

        return $user;
    }

    private function organization(string $identityId): string
    {
        $organizationId = $this->uuid();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Application Organization',
            'slug' => 'application-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);

        return $organizationId;
    }

    private function project(string $organizationId, string $identityId): string
    {
        $projectId = $this->uuid();
        $now = now();
        ProjectRecord::query()->create([
            'id' => $projectId,
            'organization_id' => $organizationId,
            'name' => 'Application Project',
            'slug' => 'application-project-'.$projectId,
            'description' => null,
            'status' => 'active',
            'created_by_identity_id' => $identityId,
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => null,
            'version' => 1,
        ]);

        return $projectId;
    }

    private function membership(string $organizationId, string $identityId): void
    {
        MembershipRecord::query()->create([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'user_identity_id' => $identityId,
            'status' => 'active',
            'activated_at' => now(),
            'version' => 1,
        ]);
    }

    private function grantApplicationCreate(string $organizationId, string $identityId): void
    {
        $roleId = $this->uuid();
        $now = now();
        $permissionId = DB::table('permissions')->where('code', 'application.create')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'application-creator-'.$roleId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('role_assignments')->insert([
            'id' => $this->uuid(),
            'organization_id' => $organizationId,
            'identity_id' => $identityId,
            'role_id' => $roleId,
            'assigned_by_identity_id' => $identityId,
            'assigned_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('role_permissions')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted_at' => $now,
            'granted_by_identity_id' => $identityId,
        ]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
