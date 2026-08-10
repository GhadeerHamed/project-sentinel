<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Organization;

use App\Infrastructure\Application\Policy\ActiveMembershipWithPermissionMayCreateApplicationPolicy;
use App\Infrastructure\Organization\Policy\ActiveHumanUserMayCreateOrganizationPolicy;
use App\Infrastructure\Organization\Policy\ActiveMembershipWithPermissionMayUpdateOrganizationPolicy;
use App\Infrastructure\Persistence\IdentityAccess\Records\IdentityUserRecord;
use App\Infrastructure\Persistence\IdentityAccess\Records\OrganizationRoleRecord;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlMembershipRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlPermissionAssignmentRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlRoleAssignmentRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlRoleProvisioningRepository;
use App\Infrastructure\Persistence\IdentityAccess\Repositories\PgsqlUserRepository;
use App\Infrastructure\Persistence\Organization\Repositories\PgsqlOrganizationRepository;
use App\Infrastructure\Persistence\Shared\Audit\PgsqlAuditRecorder;
use App\Infrastructure\Persistence\Shared\Outbox\PgsqlOutboxMessageStore;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayArchiveProjectPolicy;
use App\Infrastructure\Project\Policy\ActiveMembershipWithPermissionMayCreateProjectPolicy;
use App\Infrastructure\Shared\Transaction\LaravelTransactionManager;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\IdentityAccess\Application\Authorization\OrganizationPermissionAuthorizer;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Exception\CrossOrganizationAssignment;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleKey;
use Sentinel\Organization\Application\Command\CreateOrganizationCommand;
use Sentinel\Organization\Application\Handler\CreateOrganizationHandler;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Tenant\TenantContext;
use Sentinel\Shared\Domain\Time\Clock;
use Tests\TestCase;

final class CreateOrganizationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private const CREATOR_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e01';

    private const ORGANIZATION_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e02';

    private const MEMBERSHIP_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e03';

    private const ROLE_ASSIGNMENT_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e04';

    private const ROLE_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e05';

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL integration tests require DB_CONNECTION=pgsql.');
        }
    }

    #[Test]
    public function it_persists_complete_organization_creation_with_outbox_and_audit_records(): void
    {
        $this->seedCreator(self::CREATOR_ID);

        $fixture = $this->buildHandler();
        $result = $fixture->handler->handle($fixture->command);

        self::assertSame(self::ORGANIZATION_ID, $result->organizationId()->value());
        self::assertSame(self::MEMBERSHIP_ID, $result->creatorMembershipId()->value());
        self::assertSame(self::ROLE_ASSIGNMENT_ID, $result->initialRoleAssignmentId()->value());

        $this->assertDatabaseHas('organizations', [
            'id' => self::ORGANIZATION_ID,
            'slug' => 'acme',
            'status' => 'active',
            'version' => 1,
        ]);
        $this->assertDatabaseHas('memberships', [
            'id' => self::MEMBERSHIP_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'user_identity_id' => self::CREATOR_ID,
            'status' => 'active',
            'version' => 1,
        ]);
        $this->assertDatabaseHas('organization_roles', [
            'id' => self::ROLE_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'role_key' => 'organization_owner',
        ]);
        $this->assertDatabaseHas('role_assignments', [
            'id' => self::ROLE_ASSIGNMENT_ID,
            'organization_id' => self::ORGANIZATION_ID,
            'identity_id' => self::CREATOR_ID,
            'role_id' => self::ROLE_ID,
        ]);

        self::assertSame(
            ['organization.created', 'membership.created', 'role.assigned'],
            DB::table('outbox_messages')->orderBy('created_at')->pluck('event_type')->all(),
        );
        self::assertSame(
            ['organization.create', 'membership.create', 'role.assign'],
            DB::table('audit_records')->orderBy('recorded_at')->pluck('action')->all(),
        );
    }

    #[Test]
    public function it_bootstraps_effective_core_permissions_for_the_founding_owner(): void
    {
        $this->seedCreator(self::CREATOR_ID);
        $fixture = $this->buildHandler();

        $fixture->handler->handle($fixture->command);

        $permissions = new PgsqlPermissionAssignmentRepository;
        $effectivePermissions = $permissions->permissionsForIdentity(
            OrganizationId::fromString(self::ORGANIZATION_ID),
            IdentityId::fromString(self::CREATOR_ID),
            $this->now(),
        );

        self::assertSame(
            ['application.create', 'organization.member.manage', 'organization.suspend', 'organization.update', 'project.archive', 'project.create', 'project.read'],
            array_map(static fn ($permission): string => $permission->value(), $effectivePermissions),
        );

        $authorizer = new OrganizationPermissionAuthorizer(
            new PgsqlMembershipRepository,
            $permissions,
            new FrozenClock($this->now()),
        );
        $policy = new ActiveMembershipWithPermissionMayUpdateOrganizationPolicy($authorizer);

        self::assertTrue($policy->allows(TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::CREATOR_ID,
            identityType: 'user',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e06',
        )));

        $projectPolicy = new ActiveMembershipWithPermissionMayCreateProjectPolicy($authorizer);
        self::assertTrue($projectPolicy->allows(TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::CREATOR_ID,
            identityType: 'user',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e07',
        )));

        $projectArchivePolicy = new ActiveMembershipWithPermissionMayArchiveProjectPolicy($authorizer);
        self::assertTrue($projectArchivePolicy->allows(TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::CREATOR_ID,
            identityType: 'user',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e08',
        )));

        $applicationPolicy = new ActiveMembershipWithPermissionMayCreateApplicationPolicy($authorizer);
        self::assertTrue($applicationPolicy->allows(TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::CREATOR_ID,
            identityType: 'user',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e09',
        )));
    }

    #[Test]
    public function it_provisions_owner_role_idempotently(): void
    {
        $this->createOrganizationRecord('01890f7a-5b7c-7cc0-98c4-dc0c0c073e10');
        $repository = new PgsqlRoleProvisioningRepository(new ArrayUuidGenerator([
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e11',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e12',
        ]));

        $first = $repository->provisionRole(
            OrganizationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073e10'),
            RoleKey::organizationOwner(),
        );
        $second = $repository->provisionRole(
            OrganizationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073e10'),
            RoleKey::organizationOwner(),
        );

        self::assertSame($first->value(), $second->value());
        self::assertSame(1, DB::table('organization_roles')->count());
    }

    #[Test]
    public function it_prevents_duplicate_owner_roles_through_unique_scope(): void
    {
        $organizationId = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e20';
        $this->createOrganizationRecord($organizationId);

        OrganizationRoleRecord::query()->create([
            'id' => '01890f7a-5b7c-7cc0-98c4-dc0c0c073e21',
            'organization_id' => $organizationId,
            'role_key' => 'organization_owner',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);

        $this->expectException(QueryException::class);

        OrganizationRoleRecord::query()->create([
            'id' => '01890f7a-5b7c-7cc0-98c4-dc0c0c073e22',
            'organization_id' => $organizationId,
            'role_key' => 'organization_owner',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    #[Test]
    public function it_raises_optimistic_concurrency_conflicts_for_stale_writes(): void
    {
        $repository = new PgsqlOrganizationRepository;

        $organizationV1 = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e30',
            name: 'Acme',
            slug: 'acme-stale',
            status: 'active',
            createdByIdentityId: self::CREATOR_ID,
            createdAt: $this->now(),
            updatedAt: $this->now(),
            version: 1,
        );
        $organizationV2 = Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e30',
            name: 'Acme',
            slug: 'acme-stale',
            status: 'suspended',
            createdByIdentityId: self::CREATOR_ID,
            createdAt: $this->now(),
            updatedAt: $this->now()->modify('+1 minute'),
            version: 2,
        );

        $repository->save($organizationV1, ExpectedVersion::none());
        $repository->save($organizationV2, ExpectedVersion::fromInt(1));

        $this->expectException(OptimisticConcurrencyConflict::class);
        $repository->save($organizationV1, ExpectedVersion::fromInt(1));
    }

    #[Test]
    public function it_rejects_cross_organization_membership_lookup(): void
    {
        $repository = new PgsqlMembershipRepository;
        $this->createOrganizationRecord('01890f7a-5b7c-7cc0-98c4-dc0c0c073e42');

        $membership = Membership::establishActive(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e41',
            organizationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e42',
            userIdentityId: self::CREATOR_ID,
            actorIdentityId: self::CREATOR_ID,
            at: $this->now(),
            eventId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e43',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e44',
        );

        $repository->save($membership, ExpectedVersion::none());

        $this->expectException(MembershipNotFound::class);
        $repository->getById(
            MembershipId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073e41'),
            OrganizationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c073e99'),
        );
    }

    #[Test]
    public function it_rejects_cross_organization_role_assignments_at_persistence_boundary(): void
    {
        $organizationA = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e50';
        $organizationB = '01890f7a-5b7c-7cc0-98c4-dc0c0c073e51';
        $this->createOrganizationRecord($organizationA);
        $this->createOrganizationRecord($organizationB);

        $roleProvisioning = new PgsqlRoleProvisioningRepository(new ArrayUuidGenerator([
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e52',
        ]));
        $roleId = $roleProvisioning->provisionRole(
            OrganizationId::fromString($organizationA),
            RoleKey::organizationOwner(),
        );

        $assignment = RoleAssignment::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e53',
            organizationId: $organizationB,
            identityId: self::CREATOR_ID,
            roleId: $roleId->value(),
            roleOrganizationId: $organizationB,
            assignedByIdentityId: self::CREATOR_ID,
            assignedAt: $this->now(),
        );

        $this->expectException(CrossOrganizationAssignment::class);
        (new PgsqlRoleAssignmentRepository)->assign($assignment);
    }

    #[Test]
    public function it_rolls_back_when_repository_write_fails(): void
    {
        $this->seedCreator(self::CREATOR_ID);
        $fixture = $this->buildHandler(
            membershipRepository: new ThrowingMembershipRepository(new PgsqlMembershipRepository),
        );

        try {
            $fixture->handler->handle($fixture->command);
            self::fail('Expected repository failure.');
        } catch (\RuntimeException) {
            self::assertSame(0, DB::table('organizations')->count());
            self::assertSame(0, DB::table('memberships')->count());
            self::assertSame(0, DB::table('organization_roles')->count());
            self::assertSame(0, DB::table('role_assignments')->count());
            self::assertSame(0, DB::table('outbox_messages')->count());
            self::assertSame(0, DB::table('audit_records')->count());
        }
    }

    #[Test]
    public function it_rolls_back_when_outbox_write_fails(): void
    {
        $this->seedCreator(self::CREATOR_ID);
        $fixture = $this->buildHandler(
            outboxStore: new ThrowingOutboxMessageStore(new PgsqlOutboxMessageStore, 1),
        );

        try {
            $fixture->handler->handle($fixture->command);
            self::fail('Expected outbox failure.');
        } catch (\RuntimeException) {
            self::assertSame(0, DB::table('organizations')->count());
            self::assertSame(0, DB::table('memberships')->count());
            self::assertSame(0, DB::table('organization_roles')->count());
            self::assertSame(0, DB::table('role_assignments')->count());
            self::assertSame(0, DB::table('outbox_messages')->count());
            self::assertSame(0, DB::table('audit_records')->count());
        }
    }

    #[Test]
    public function it_rolls_back_when_audit_write_fails(): void
    {
        $this->seedCreator(self::CREATOR_ID);
        $fixture = $this->buildHandler(
            auditRecorder: new ThrowingAuditRecorder(new PgsqlAuditRecorder, 1),
        );

        try {
            $fixture->handler->handle($fixture->command);
            self::fail('Expected audit failure.');
        } catch (\RuntimeException) {
            self::assertSame(0, DB::table('organizations')->count());
            self::assertSame(0, DB::table('memberships')->count());
            self::assertSame(0, DB::table('organization_roles')->count());
            self::assertSame(0, DB::table('role_assignments')->count());
            self::assertSame(0, DB::table('outbox_messages')->count());
            self::assertSame(0, DB::table('audit_records')->count());
        }
    }

    #[Test]
    public function it_hydrates_aggregates_without_replaying_historical_domain_events(): void
    {
        $this->seedCreator(self::CREATOR_ID);
        $fixture = $this->buildHandler();
        $fixture->handler->handle($fixture->command);

        $organization = (new PgsqlOrganizationRepository)->getById(
            OrganizationId::fromString(self::ORGANIZATION_ID),
        );
        $membership = (new PgsqlMembershipRepository)->getById(
            MembershipId::fromString(self::MEMBERSHIP_ID),
            OrganizationId::fromString(self::ORGANIZATION_ID),
        );
        $assignments = (new PgsqlRoleAssignmentRepository)->activeForIdentity(
            OrganizationId::fromString(self::ORGANIZATION_ID),
            IdentityId::fromString(self::CREATOR_ID),
            $this->now()->modify('+1 hour'),
        );

        self::assertCount(0, $organization->pullDomainEvents());
        self::assertCount(0, $membership->pullDomainEvents());
        self::assertCount(1, $assignments);
        self::assertCount(0, $assignments[0]->pullDomainEvents());
    }

    private function seedCreator(string $identityId): void
    {
        IdentityUserRecord::query()->create([
            'identity_id' => $identityId,
            'email' => 'owner@example.com',
            'display_name' => 'Owner',
            'status' => 'active',
            'version' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function createOrganizationRecord(string $organizationId): void
    {
        DB::table('organizations')->insert([
            'id' => $organizationId,
            'name' => 'Acme',
            'slug' => sprintf('org-%s', substr($organizationId, -4)),
            'created_by_identity_id' => self::CREATOR_ID,
            'status' => 'active',
            'version' => 1,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-02T14:40:00+00:00');
    }

    private function buildHandler(
        ?MembershipRepository $membershipRepository = null,
        ?OutboxMessageStore $outboxStore = null,
        ?AuditRecorder $auditRecorder = null,
    ): CreateOrganizationFixture {
        $uuidGenerator = new ArrayUuidGenerator($this->uuidSequence());

        $handler = new CreateOrganizationHandler(
            userRepository: new PgsqlUserRepository,
            organizationRepository: new PgsqlOrganizationRepository,
            membershipRepository: $membershipRepository ?? new PgsqlMembershipRepository,
            roleAssignmentRepository: new PgsqlRoleAssignmentRepository,
            roleProvisioningRepository: new PgsqlRoleProvisioningRepository($uuidGenerator),
            permissionAssignmentRepository: new PgsqlPermissionAssignmentRepository,
            organizationCreationPolicy: new ActiveHumanUserMayCreateOrganizationPolicy,
            uuidGenerator: $uuidGenerator,
            clock: new FrozenClock($this->now()),
            transactionManager: new LaravelTransactionManager,
            outboxMessageStore: $outboxStore ?? new PgsqlOutboxMessageStore,
            auditRecorder: $auditRecorder ?? new PgsqlAuditRecorder,
        );

        return new CreateOrganizationFixture(
            handler: $handler,
            command: CreateOrganizationCommand::fromValues(
                organizationName: 'Acme',
                organizationSlug: 'acme',
                creatorIdentityId: self::CREATOR_ID,
                creatorIdentityType: 'user',
                correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073e06',
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function uuidSequence(): array
    {
        return [
            self::ORGANIZATION_ID,
            self::MEMBERSHIP_ID,
            self::ROLE_ASSIGNMENT_ID,
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e07',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e08',
            self::ROLE_ID,
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e09',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0a',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0b',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0c',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0d',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0e',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c073e0f',
        ];
    }
}

final readonly class CreateOrganizationFixture
{
    public function __construct(
        public CreateOrganizationHandler $handler,
        public CreateOrganizationCommand $command,
    ) {}
}

final readonly class FrozenClock implements Clock
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class ArrayUuidGenerator implements UuidV7Generator
{
    /**
     * @param  list<string>  $values
     */
    public function __construct(
        private array $values,
    ) {}

    public function generate(): string
    {
        $value = array_shift($this->values);

        if ($value === null) {
            throw new \RuntimeException('UUID sequence exhausted.');
        }

        return $value;
    }
}

final readonly class ThrowingMembershipRepository implements MembershipRepository
{
    public function __construct(
        private MembershipRepository $inner,
    ) {}

    public function getById(
        MembershipId $membershipId,
        OrganizationId $organizationId,
    ): Membership {
        return $this->inner->getById($membershipId, $organizationId);
    }

    public function getByIdentity(
        OrganizationId $organizationId,
        IdentityId $identityId,
    ): Membership {
        return $this->inner->getByIdentity($organizationId, $identityId);
    }

    public function save(Membership $membership, ExpectedVersion $expectedVersion): void
    {
        throw new \RuntimeException('Simulated membership repository failure.');
    }
}

final class ThrowingOutboxMessageStore implements OutboxMessageStore
{
    private int $calls = 0;

    public function __construct(
        private readonly OutboxMessageStore $inner,
        private readonly int $failOnCall,
    ) {}

    public function append(OutboxMessage $message): void
    {
        $this->calls++;

        if ($this->calls === $this->failOnCall) {
            throw new \RuntimeException('Simulated outbox failure.');
        }

        $this->inner->append($message);
    }
}

final class ThrowingAuditRecorder implements AuditRecorder
{
    private int $calls = 0;

    public function __construct(
        private readonly AuditRecorder $inner,
        private readonly int $failOnCall,
    ) {}

    public function record(AuditRecord $auditRecord): void
    {
        $this->calls++;

        if ($this->calls === $this->failOnCall) {
            throw new \RuntimeException('Simulated audit failure.');
        }

        $this->inner->record($auditRecord);
    }
}
