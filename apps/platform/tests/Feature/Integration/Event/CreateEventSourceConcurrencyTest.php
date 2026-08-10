<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Event;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Event\Application\Authentication\EventSourceCredentialIssuer;
use Sentinel\Event\Application\Command\RegisterEventSourceCommand;
use Sentinel\Event\Application\Handler\RegisterEventSourceHandler;
use Sentinel\Event\Application\Policy\EventSourceRegistrationPolicy;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Tests\TestCase;
use Throwable;

final class CreateEventSourceConcurrencyTest extends TestCase
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
    public function concurrent_identical_issuance_requests_create_one_source_and_one_credential(): void
    {
        [$organizationId, $projectId, $applicationId, $environmentId, $identityId] = $this->fixture();
        $firstPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        $secondPair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($firstPair);
        self::assertIsArray($secondPair);

        $firstPid = $this->forkIssue($firstPair, $organizationId, $projectId, $applicationId, $environmentId, $identityId);
        $secondPid = $this->forkIssue($secondPair, $organizationId, $projectId, $applicationId, $environmentId, $identityId);
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
        $this->assertDatabaseCount('event_sources', 1);
        $this->assertDatabaseCount('event_source_credentials', 1);
        $this->assertDatabaseCount('idempotency_records', 1);
        $this->assertDatabaseCount('audit_records', 2);
    }

    /** @param array{resource, resource} $pair */
    private function forkIssue(array $pair, string $organizationId, string $projectId, string $applicationId, string $environmentId, string $identityId): int
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
            $result = $this->handler($organizationId, $identityId)->handle(RegisterEventSourceCommand::fromValues(
                projectId: $projectId,
                applicationId: $applicationId,
                environmentId: $environmentId,
                name: 'Concurrent API Client',
                idempotencyKey: 'concurrent-event-source-create',
            ));
            fwrite($pair[1], 'ok:'.$result->eventSource()->id()->value()."\n");
            fclose($pair[1]);

            exit(0);
        } catch (Throwable $exception) {
            fwrite($pair[1], 'error:'.$exception::class.':'.$exception->getMessage()."\n");
            fclose($pair[1]);

            exit(1);
        }
    }

    private function handler(string $organizationId, string $identityId): RegisterEventSourceHandler
    {
        $tenantContext = TenantContext::fromValues(
            organizationId: $organizationId,
            identityId: $identityId,
            identityType: 'user',
            correlationId: $this->uuid(),
        );

        return new RegisterEventSourceHandler(
            eventSources: $this->app->make(EventSourceRepository::class),
            credentials: $this->app->make(EventSourceCredentialRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applications: $this->app->make(ApplicationRepository::class),
            environments: $this->app->make(EnvironmentRepository::class),
            registrationPolicy: $this->app->make(EventSourceRegistrationPolicy::class),
            tenantContext: $tenantContext,
            idempotentOperation: new IdempotentOperation(
                $this->app->make(TransactionManager::class),
                $this->app->make(IdempotencyRepository::class),
                $this->app->make(UuidV7Generator::class),
                $this->app->make(Clock::class),
            ),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            credentialIssuer: new EventSourceCredentialIssuer,
            audit: $this->app->make(AuditRecorder::class),
        );
    }

    /** @return array{string, string, string, string, string} */
    private function fixture(): array
    {
        $organizationId = $this->uuid();
        $projectId = $this->uuid();
        $applicationId = $this->uuid();
        $environmentId = $this->uuid();
        $identityId = $this->uuid();
        $roleId = $this->uuid();
        $now = now();
        OrganizationRecord::query()->create(['id' => $organizationId, 'name' => 'Concurrent Event Source Organization', 'slug' => 'concurrent-event-source-organization-'.$organizationId, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);
        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Concurrent Project', 'slug' => 'concurrent-project-'.$projectId, 'description' => null, 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Concurrent Application', 'slug' => 'concurrent-application-'.$applicationId, 'description' => null, 'criticality' => 'high', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => 'Concurrent Environment', 'slug' => 'concurrent-environment-'.$environmentId, 'environment_type' => 'production', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => $now, 'version' => 1]);
        $permissionId = DB::table('permissions')->where('code', 'event_source.create')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'event-source-issuer-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);

        return [$organizationId, $projectId, $applicationId, $environmentId, $identityId];
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
