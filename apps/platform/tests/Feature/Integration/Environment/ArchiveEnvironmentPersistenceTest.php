<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Environment;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Sentinel\Application\Application\Query\ApplicationLookupQuery;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Command\ArchiveEnvironmentCommand;
use Sentinel\Environment\Application\Handler\ArchiveEnvironmentHandler;
use Sentinel\Environment\Application\Policy\EnvironmentArchivalPolicy;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Application\Query\ProjectLookupQuery;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Application\TenantScopedOperationRecorder;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Tests\TestCase;

final class ArchiveEnvironmentPersistenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_persists_environment_archived_outbox_and_environment_archive_audit_records(): void
    {
        $fixture = $this->archiveFixture();

        $this->handler($fixture->tenantContext)->handle($fixture->command);

        $this->assertDatabaseHas('outbox_messages', [
            'organization_id' => $fixture->organizationId,
            'event_type' => 'environment.archived',
            'aggregate_type' => 'environment',
            'aggregate_id' => $fixture->environmentId,
            'aggregate_version' => 2,
        ]);
        $this->assertDatabaseHas('audit_records', [
            'organization_id' => $fixture->organizationId,
            'action' => 'environment.archive',
            'resource_type' => 'environment',
            'resource_id' => $fixture->environmentId,
        ]);
    }

    #[Test]
    public function it_rolls_back_environment_outbox_and_audit_when_audit_recording_fails(): void
    {
        $fixture = $this->archiveFixture();

        try {
            $this->handler($fixture->tenantContext, new ThrowingEnvironmentAuditRecorder)->handle($fixture->command);
            self::fail('Expected audit recorder failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated environment audit recorder failure.', $exception->getMessage());
        }

        $this->assertDatabaseHas('environments', [
            'id' => $fixture->environmentId,
            'status' => 'active',
            'version' => 1,
            'archived_at' => null,
        ]);
        $this->assertDatabaseCount('outbox_messages', 0);
        $this->assertDatabaseCount('audit_records', 0);
    }

    #[Test]
    public function it_backfills_environment_archive_for_existing_organization_owner_roles(): void
    {
        $migration = require database_path('migrations/2026_08_10_080000_add_environment_archive_permission.php');
        $migration->down();

        $organizationId = $this->uuid();
        $identityId = $this->uuid();
        $roleId = $this->uuid();
        $now = now();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Existing Organization',
            'slug' => 'existing-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'organization_owner',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration->up();

        $permissionId = DB::table('permissions')->where('code', 'environment.archive')->value('id');
        self::assertIsString($permissionId);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted_by_identity_id' => $identityId,
        ]);
    }

    private function archiveFixture(): ArchiveEnvironmentFixture
    {
        $organizationId = $this->uuid();
        $identityId = $this->uuid();
        $projectId = $this->uuid();
        $applicationId = $this->uuid();
        $environmentId = $this->uuid();
        $now = now();

        OrganizationRecord::query()->create(['id' => $organizationId, 'name' => 'Environment Organization', 'slug' => 'environment-organization-'.$organizationId, 'timezone' => 'UTC', 'created_by_identity_id' => $identityId, 'status' => 'active', 'version' => 1]);
        ProjectRecord::query()->create(['id' => $projectId, 'organization_id' => $organizationId, 'name' => 'Environment Project', 'slug' => 'environment-project-'.$projectId, 'description' => null, 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        ApplicationRecord::query()->create(['id' => $applicationId, 'organization_id' => $organizationId, 'project_id' => $projectId, 'name' => 'Environment Application', 'slug' => 'environment-application-'.$applicationId, 'description' => null, 'criticality' => 'high', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        EnvironmentRecord::query()->create(['id' => $environmentId, 'organization_id' => $organizationId, 'application_id' => $applicationId, 'name' => 'Production', 'slug' => 'production', 'environment_type' => 'production', 'status' => 'active', 'created_by_identity_id' => $identityId, 'created_at' => $now, 'updated_at' => $now, 'archived_at' => null, 'version' => 1]);
        MembershipRecord::query()->create(['id' => $this->uuid(), 'organization_id' => $organizationId, 'user_identity_id' => $identityId, 'status' => 'active', 'activated_at' => $now, 'version' => 1]);
        $this->grantEnvironmentArchive($organizationId, $identityId, $now);

        return new ArchiveEnvironmentFixture(
            organizationId: $organizationId,
            environmentId: $environmentId,
            tenantContext: TenantContext::fromValues($organizationId, $identityId, 'user', $this->uuid()),
            command: new ArchiveEnvironmentCommand(
                ProjectId::fromString($projectId),
                ApplicationId::fromString($applicationId),
                EnvironmentId::fromString($environmentId),
                ExpectedVersion::fromInt(1),
            ),
        );
    }

    private function handler(TenantContext $tenantContext, ?AuditRecorder $auditRecorder = null): ArchiveEnvironmentHandler
    {
        return new ArchiveEnvironmentHandler(
            environments: $this->app->make(EnvironmentRepository::class),
            projects: $this->app->make(ProjectLookupQuery::class),
            applications: $this->app->make(ApplicationLookupQuery::class),
            environmentArchivalPolicy: $this->app->make(EnvironmentArchivalPolicy::class),
            tenantContext: $tenantContext,
            clock: $this->app->make(Clock::class),
            transactions: $this->app->make(TransactionManager::class),
            uuidGenerator: $this->app->make(UuidV7Generator::class),
            operationRecorder: new TenantScopedOperationRecorder(
                tenantContext: $tenantContext,
                uuidGenerator: $this->app->make(UuidV7Generator::class),
                outbox: $this->app->make(OutboxMessageStore::class),
                audit: $auditRecorder ?? $this->app->make(AuditRecorder::class),
            ),
        );
    }

    private function grantEnvironmentArchive(string $organizationId, string $identityId, \DateTimeInterface $now): void
    {
        $roleId = $this->uuid();
        $permissionId = DB::table('permissions')->where('code', 'environment.archive')->value('id');
        self::assertIsString($permissionId);
        DB::table('organization_roles')->insert(['id' => $roleId, 'organization_id' => $organizationId, 'role_key' => 'environment-archiver-'.$roleId, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_assignments')->insert(['id' => $this->uuid(), 'organization_id' => $organizationId, 'identity_id' => $identityId, 'role_id' => $roleId, 'assigned_by_identity_id' => $identityId, 'assigned_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'granted_at' => $now, 'granted_by_identity_id' => $identityId]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}

final readonly class ArchiveEnvironmentFixture
{
    public function __construct(
        public string $organizationId,
        public string $environmentId,
        public TenantContext $tenantContext,
        public ArchiveEnvironmentCommand $command,
    ) {}
}

final class ThrowingEnvironmentAuditRecorder implements AuditRecorder
{
    public function record(AuditRecord $auditRecord): void
    {
        throw new RuntimeException('Simulated environment audit recorder failure.');
    }
}
