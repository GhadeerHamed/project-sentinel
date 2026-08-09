<?php

declare(strict_types=1);

namespace Tests\Application\Organization;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Entity\RoleAssignment;
use Sentinel\IdentityAccess\Domain\Entity\User;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Exception\RoleAssignmentNotFound;
use Sentinel\IdentityAccess\Domain\Exception\UserNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\PermissionAssignmentRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleAssignmentRepository;
use Sentinel\IdentityAccess\Domain\Repository\RoleProvisioningRepository;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleKey;
use Sentinel\Organization\Application\Command\CreateOrganizationCommand;
use Sentinel\Organization\Application\Handler\CreateOrganizationHandler;
use Sentinel\Organization\Application\Policy\OrganizationCreationPolicy;
use Sentinel\Organization\Application\Result\CreateOrganizationResult;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Exception\OrganizationNotFound;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class CreateOrganizationHandlerTest extends TestCase
{
    #[Test]
    public function it_creates_organization_founding_membership_owner_assignment_outbox_and_audit_atomically(): void
    {
        $fixture = $this->fixture();

        $result = $fixture->handler->handle($fixture->command);

        self::assertInstanceOf(CreateOrganizationResult::class, $result);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c075001', $result->organizationId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c075002', $result->creatorMembershipId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c075003', $result->initialRoleAssignmentId()->value());

        self::assertCount(1, $fixture->organizationRepository->persisted());
        self::assertCount(1, $fixture->membershipRepository->persisted());
        self::assertCount(1, $fixture->roleAssignmentRepository->persisted());
        self::assertSame(
            ['organization.member.manage', 'organization.update', 'organization.suspend'],
            array_map(
                static fn (array $assignment): string => $assignment['permissionCode']->value(),
                $fixture->permissionAssignmentRepository->persisted(),
            ),
        );
        self::assertSame('active', array_values($fixture->membershipRepository->persisted())[0]->status()->value);
        self::assertSame(1, array_values($fixture->membershipRepository->persisted())[0]->version()->value());

        self::assertSame(['organization.created', 'membership.created', 'role.assigned'], $fixture->outboxStore->persistedEventTypes());
        self::assertSame([1, 1, 1], $fixture->outboxStore->persistedEventSchemaVersions());
        self::assertSame([1, 1, 1], $fixture->outboxStore->persistedAggregateVersions());
        self::assertSame(
            ['organization', 'membership', 'role_assignment'],
            $fixture->outboxStore->persistedAggregateTypes(),
        );

        self::assertSame(3, count($fixture->auditRecorder->persisted()));
        self::assertSame(
            ['organization.create', 'membership.create', 'role.assign'],
            array_map(static fn (AuditRecord $record): string => $record->action(), $fixture->auditRecorder->persisted()),
        );

        self::assertSame(RoleKey::organizationOwner()->value(), $fixture->roleProvisioningRepository->lastRoleKey?->value());
        self::assertSame($result->organizationId()->value(), $fixture->roleProvisioningRepository->lastOrganizationId?->value());

        self::assertSame(1, $fixture->policy->calls);
        self::assertSame($fixture->command->creator()->identityId(), $fixture->policy->lastCreator?->identityId());

        self::assertSame(1, $fixture->transactionManager->beginCount);
        self::assertSame(1, $fixture->transactionManager->commitCount);
        self::assertSame(0, $fixture->transactionManager->rollBackCount);
    }

    #[Test]
    public function it_requires_creator_to_exist(): void
    {
        $fixture = $this->fixture(includeCreator: false);

        $this->expectException(UserNotFound::class);

        $fixture->handler->handle($fixture->command);
    }

    #[Test]
    public function it_rejects_non_user_creator_identity_types(): void
    {
        $fixture = $this->fixture(creatorIdentityType: 'plugin');

        try {
            $fixture->handler->handle($fixture->command);
            self::fail('Expected non-user creator identity to be rejected.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertSame(0, $fixture->policy->calls);
        }
    }

    #[Test]
    public function it_rejects_non_authenticatable_creator_statuses(): void
    {
        foreach (['suspended', 'disabled', 'compromised'] as $status) {
            $fixture = $this->fixture(creatorStatus: $status);

            try {
                $fixture->handler->handle($fixture->command);
                self::fail('Expected creator eligibility failure for status '.$status);
            } catch (ApplicationOperationFailure $error) {
                self::assertSame(ErrorCategory::INVALID_STATE, $error->category());
                self::assertCount(0, $fixture->organizationRepository->persisted());
                self::assertCount(0, $fixture->membershipRepository->persisted());
                self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
                self::assertCount(0, $fixture->outboxStore->persisted());
                self::assertCount(0, $fixture->auditRecorder->persisted());
                self::assertSame(0, $fixture->policy->calls);
                self::assertSame(1, $fixture->transactionManager->rollBackCount);
            }
        }
    }

    #[Test]
    public function authorization_denial_prevents_persistence(): void
    {
        $fixture = $this->fixture(policyAllows: false);

        try {
            $fixture->handler->handle($fixture->command);
            self::fail('Expected authorization failure.');
        } catch (ApplicationOperationFailure $error) {
            self::assertSame(ErrorCategory::FORBIDDEN, $error->category());
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
        }
    }

    #[Test]
    public function organization_repository_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->organizationRepository->failOnSave = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function membership_repository_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->membershipRepository->failOnSave = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function role_assignment_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->roleAssignmentRepository->failOnAssign = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function permission_assignment_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->permissionAssignmentRepository->failOnAssign = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->permissionAssignmentRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function outbox_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->outboxStore->failOnAppendCall = 2;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function audit_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->auditRecorder->failOnRecordCall = 3;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function creation_rejects_incorrect_expected_version_semantics(): void
    {
        $fixture = $this->fixture(organizationIdOverride: '01890f7a-5b7c-7cc0-98c4-dc0c0c075001');
        $fixture->organizationRepository->seed(Organization::fromValues(
            id: '01890f7a-5b7c-7cc0-98c4-dc0c0c075001',
            name: 'Existing Org',
            slug: 'existing-org',
            status: 'active',
            createdByIdentityId: $fixture->creatorIdentityId,
            createdAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
            version: 1,
        ));

        $this->expectException(OptimisticConcurrencyConflict::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(1, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->roleAssignmentRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
        }
    }

    #[Test]
    public function handler_depends_only_on_framework_independent_contracts(): void
    {
        $constructor = (new \ReflectionClass(CreateOrganizationHandler::class))->getConstructor();

        foreach ($constructor?->getParameters() ?? [] as $parameter) {
            $typeName = $parameter->getType()?->getName() ?? '';

            self::assertStringNotContainsString('Illuminate\\', $typeName);
            self::assertStringNotContainsString('Laravel\\', $typeName);
        }
    }

    private function fixture(
        bool $includeCreator = true,
        string $creatorStatus = 'active',
        string $creatorIdentityType = 'user',
        bool $policyAllows = true,
        ?string $organizationIdOverride = null,
    ): CreateOrganizationFixture {
        $transactionManager = new FakeTransactionManager;
        $clock = new FakeClock(new DateTimeImmutable('2026-08-02T16:00:00+00:00'));
        $creatorIdentityId = '01890f7a-5b7c-7cc0-98c4-dc0c0c075000';

        $ids = [
            $organizationIdOverride ?? '01890f7a-5b7c-7cc0-98c4-dc0c0c075001',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075002',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075003',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075004',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075005',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075006',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075007',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075008',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075009',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075010',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075011',
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075012',
        ];
        $uuidGenerator = new FakeUuidGenerator($ids);
        $policy = new FakeOrganizationCreationPolicy($policyAllows);

        $users = [];
        if ($includeCreator) {
            $users[$creatorIdentityId] = User::fromValues(
                identityId: $creatorIdentityId,
                email: 'creator@example.com',
                displayName: 'Organization Creator',
                status: $creatorStatus,
                createdAt: new DateTimeImmutable('2026-08-02T15:00:00+00:00'),
                updatedAt: new DateTimeImmutable('2026-08-02T15:00:00+00:00'),
                version: 1,
            );
        }
        $userRepository = new FakeUserRepository($users);

        $organizationRepository = new FakeOrganizationRepository;
        $membershipRepository = new FakeMembershipRepository;
        $roleAssignmentRepository = new FakeRoleAssignmentRepository;
        $roleProvisioningRepository = new FakeRoleProvisioningRepository(RoleId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c075099'));
        $permissionAssignmentRepository = new FakePermissionAssignmentRepository;
        $outboxStore = new FakeOutboxMessageStore;
        $auditRecorder = new FakeAuditRecorder;

        $transactionManager->register($organizationRepository);
        $transactionManager->register($membershipRepository);
        $transactionManager->register($roleAssignmentRepository);
        $transactionManager->register($permissionAssignmentRepository);
        $transactionManager->register($outboxStore);
        $transactionManager->register($auditRecorder);

        $handler = new CreateOrganizationHandler(
            userRepository: $userRepository,
            organizationRepository: $organizationRepository,
            membershipRepository: $membershipRepository,
            roleAssignmentRepository: $roleAssignmentRepository,
            roleProvisioningRepository: $roleProvisioningRepository,
            permissionAssignmentRepository: $permissionAssignmentRepository,
            organizationCreationPolicy: $policy,
            uuidGenerator: $uuidGenerator,
            clock: $clock,
            transactionManager: $transactionManager,
            outboxMessageStore: $outboxStore,
            auditRecorder: $auditRecorder,
        );

        $command = CreateOrganizationCommand::fromValues(
            organizationName: 'Sentinel Org',
            organizationSlug: 'sentinel-org',
            creatorIdentityId: $creatorIdentityId,
            creatorIdentityType: $creatorIdentityType,
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c075090',
            causationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c075091',
        );

        return new CreateOrganizationFixture(
            handler: $handler,
            command: $command,
            creatorIdentityId: $creatorIdentityId,
            transactionManager: $transactionManager,
            organizationRepository: $organizationRepository,
            membershipRepository: $membershipRepository,
            roleAssignmentRepository: $roleAssignmentRepository,
            roleProvisioningRepository: $roleProvisioningRepository,
            permissionAssignmentRepository: $permissionAssignmentRepository,
            outboxStore: $outboxStore,
            auditRecorder: $auditRecorder,
            policy: $policy,
        );
    }
}

final readonly class CreateOrganizationFixture
{
    public function __construct(
        public CreateOrganizationHandler $handler,
        public CreateOrganizationCommand $command,
        public string $creatorIdentityId,
        public FakeTransactionManager $transactionManager,
        public FakeOrganizationRepository $organizationRepository,
        public FakeMembershipRepository $membershipRepository,
        public FakeRoleAssignmentRepository $roleAssignmentRepository,
        public FakeRoleProvisioningRepository $roleProvisioningRepository,
        public FakePermissionAssignmentRepository $permissionAssignmentRepository,
        public FakeOutboxMessageStore $outboxStore,
        public FakeAuditRecorder $auditRecorder,
        public FakeOrganizationCreationPolicy $policy,
    ) {}
}

interface FakeTransactionalParticipant
{
    public function beginTransaction(): void;

    public function commitTransaction(): void;

    public function rollBackTransaction(): void;
}

final class FakeTransactionManager implements TransactionManager
{
    /** @var list<FakeTransactionalParticipant> */
    private array $participants = [];

    public int $beginCount = 0;

    public int $commitCount = 0;

    public int $rollBackCount = 0;

    public bool $failAfterOperation = false;

    public function register(FakeTransactionalParticipant $participant): void
    {
        $this->participants[] = $participant;
    }

    public function begin(): void
    {
        $this->beginCount++;

        foreach ($this->participants as $participant) {
            $participant->beginTransaction();
        }
    }

    public function commit(): void
    {
        $this->commitCount++;
    }

    public function rollBack(): void
    {
        $this->rollBackCount++;

        foreach ($this->participants as $participant) {
            $participant->rollBackTransaction();
        }
    }

    public function transactional(callable $operation): mixed
    {
        $this->begin();

        try {
            $result = $operation();

            if ($this->failAfterOperation) {
                throw new RuntimeException('Simulated transaction failure.');
            }

            foreach ($this->participants as $participant) {
                $participant->commitTransaction();
            }

            $this->commit();

            return $result;
        } catch (\Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }
}

final readonly class FakeClock implements Clock
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}

final class FakeUuidGenerator implements UuidV7Generator
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
            throw new RuntimeException('UUID generator exhausted.');
        }

        return $value;
    }
}

final class FakeOrganizationCreationPolicy implements OrganizationCreationPolicy
{
    public int $calls = 0;

    public ?IdentityReference $lastCreator = null;

    public function __construct(
        private bool $allows,
    ) {}

    public function allows(IdentityReference $creator): bool
    {
        $this->calls++;
        $this->lastCreator = $creator;

        return $this->allows;
    }
}

final class FakeUserRepository implements UserRepository
{
    /**
     * @param  array<string, User>  $users
     */
    public function __construct(
        private array $users,
    ) {}

    public function getByIdentityId(IdentityId $identityId): User
    {
        return $this->users[$identityId->value()] ?? throw UserNotFound::forIdentity($identityId);
    }

    public function save(User $user, ExpectedVersion $expectedVersion): void
    {
        $current = isset($this->users[$user->identityId()->value()])
            ? CurrentVersion::fromInt($this->users[$user->identityId()->value()]->version()->value())
            : CurrentVersion::none();
        $expectedVersion->assertMatches($current);

        $this->users[$user->identityId()->value()] = $user;
    }
}

final class FakeOrganizationRepository implements FakeTransactionalParticipant, OrganizationRepository
{
    /** @var array<string, Organization> */
    private array $persisted = [];

    /** @var array<string, Organization> */
    private array $pending = [];

    public bool $failOnSave = false;

    public function getById(OrganizationId $organizationId): Organization
    {
        return $this->pending[$organizationId->value()]
            ?? $this->persisted[$organizationId->value()]
            ?? throw OrganizationNotFound::forId($organizationId);
    }

    public function save(Organization $organization, ExpectedVersion $expectedVersion): void
    {
        if ($this->failOnSave) {
            throw new RuntimeException('Organization repository save failed.');
        }

        $id = $organization->id()->value();
        $current = isset($this->persisted[$id])
            ? CurrentVersion::fromInt($this->persisted[$id]->version()->value())
            : CurrentVersion::none();

        $expectedVersion->assertMatches($current);
        $this->pending[$id] = $organization;
    }

    public function accessibleTo(IdentityId $identityId): array
    {
        return [];
    }

    public function seed(Organization $organization): void
    {
        $this->persisted[$organization->id()->value()] = $organization;
    }

    /**
     * @return array<string, Organization>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        foreach ($this->pending as $id => $organization) {
            $this->persisted[$id] = $organization;
        }

        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}

final class FakeMembershipRepository implements FakeTransactionalParticipant, MembershipRepository
{
    /** @var array<string, Membership> */
    private array $persisted = [];

    /** @var array<string, Membership> */
    private array $pending = [];

    public bool $failOnSave = false;

    public function getById(MembershipId $membershipId, OrganizationId $organizationId): Membership
    {
        $membership = $this->pending[$membershipId->value()]
            ?? $this->persisted[$membershipId->value()]
            ?? throw MembershipNotFound::forId($membershipId, $organizationId);

        $membership->assertOrganizationScope($organizationId->value());

        return $membership;
    }

    public function getByIdentity(OrganizationId $organizationId, IdentityId $identityId): Membership
    {
        foreach ([$this->pending, $this->persisted] as $store) {
            foreach ($store as $membership) {
                if (
                    $membership->organizationId()->value() === $organizationId->value()
                    && $membership->userIdentityId()->value() === $identityId->value()
                ) {
                    return $membership;
                }
            }
        }

        throw MembershipNotFound::forIdentity($identityId, $organizationId);
    }

    public function save(Membership $membership, ExpectedVersion $expectedVersion): void
    {
        if ($this->failOnSave) {
            throw new RuntimeException('Membership repository save failed.');
        }

        $id = $membership->id()->value();
        $current = isset($this->persisted[$id])
            ? CurrentVersion::fromInt($this->persisted[$id]->version()->value())
            : CurrentVersion::none();

        $expectedVersion->assertMatches($current);
        $this->pending[$id] = $membership;
    }

    /**
     * @return array<string, Membership>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        foreach ($this->pending as $id => $membership) {
            $this->persisted[$id] = $membership;
        }

        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}

final class FakeRoleAssignmentRepository implements FakeTransactionalParticipant, RoleAssignmentRepository
{
    /** @var array<string, RoleAssignment> */
    private array $persisted = [];

    /** @var array<string, RoleAssignment> */
    private array $pending = [];

    public bool $failOnAssign = false;

    public function assign(RoleAssignment $assignment): void
    {
        if ($this->failOnAssign) {
            throw new RuntimeException('Role assignment save failed.');
        }

        $this->pending[$assignment->id()->value()] = $assignment;
    }

    public function getById(RoleAssignmentId $assignmentId, OrganizationId $organizationId): RoleAssignment
    {
        $assignment = $this->pending[$assignmentId->value()] ?? $this->persisted[$assignmentId->value()] ?? null;

        if ($assignment === null || ! $assignment->organizationId()->equals($organizationId)) {
            throw RoleAssignmentNotFound::forId($assignmentId, $organizationId);
        }

        return $assignment;
    }

    public function revoke(
        RoleAssignmentId $assignmentId,
        OrganizationId $organizationId,
        IdentityId $revokedBy,
        DateTimeImmutable $revokedAt,
    ): void {
        throw new RuntimeException('Not implemented in test fake.');
    }

    public function activeForIdentity(
        OrganizationId $organizationId,
        IdentityId $identityId,
        DateTimeImmutable $at,
    ): array {
        throw new RuntimeException('Not implemented in test fake.');
    }

    /**
     * @return array<string, RoleAssignment>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        foreach ($this->pending as $id => $assignment) {
            $this->persisted[$id] = $assignment;
        }

        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}

final class FakeRoleProvisioningRepository implements RoleProvisioningRepository
{
    public ?OrganizationId $lastOrganizationId = null;

    public ?RoleKey $lastRoleKey = null;

    public function __construct(
        private readonly RoleId $resolvedRoleId,
    ) {}

    public function provisionRole(OrganizationId $organizationId, RoleKey $roleKey): RoleId
    {
        $this->lastOrganizationId = $organizationId;
        $this->lastRoleKey = $roleKey;

        return $this->resolvedRoleId;
    }
}

final class FakePermissionAssignmentRepository implements FakeTransactionalParticipant, PermissionAssignmentRepository
{
    /**
     * @var list<array{organizationId: OrganizationId, roleId: RoleId, permissionCode: PermissionCode, grantedBy: IdentityId, grantedAt: DateTimeImmutable}>
     */
    private array $persisted = [];

    /**
     * @var list<array{organizationId: OrganizationId, roleId: RoleId, permissionCode: PermissionCode, grantedBy: IdentityId, grantedAt: DateTimeImmutable}>
     */
    private array $pending = [];

    public bool $failOnAssign = false;

    public function assignToRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $grantedBy, DateTimeImmutable $grantedAt): void
    {
        if ($this->failOnAssign) {
            throw new RuntimeException('Permission assignment failed.');
        }

        $this->pending[] = [
            'organizationId' => $organizationId,
            'roleId' => $roleId,
            'permissionCode' => $permissionCode,
            'grantedBy' => $grantedBy,
            'grantedAt' => $grantedAt,
        ];
    }

    public function revokeFromRole(OrganizationId $organizationId, RoleId $roleId, PermissionCode $permissionCode, IdentityId $revokedBy, DateTimeImmutable $revokedAt): void
    {
        throw new RuntimeException('Not implemented in test fake.');
    }

    public function permissionsForIdentity(OrganizationId $organizationId, IdentityId $identityId, DateTimeImmutable $at): array
    {
        return [];
    }

    /**
     * @return list<array{organizationId: OrganizationId, roleId: RoleId, permissionCode: PermissionCode, grantedBy: IdentityId, grantedAt: DateTimeImmutable}>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        $this->persisted = [...$this->persisted, ...$this->pending];
        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}

final class FakeOutboxMessageStore implements FakeTransactionalParticipant, OutboxMessageStore
{
    /** @var list<OutboxMessage> */
    private array $persisted = [];

    /** @var list<OutboxMessage> */
    private array $pending = [];

    public ?int $failOnAppendCall = null;

    private int $appendCalls = 0;

    public function append(OutboxMessage $message): void
    {
        $this->appendCalls++;

        if ($this->failOnAppendCall !== null && $this->appendCalls === $this->failOnAppendCall) {
            throw new RuntimeException('Outbox append failed.');
        }

        $this->pending[] = $message;
    }

    /**
     * @return list<OutboxMessage>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    /**
     * @return list<string>
     */
    public function persistedEventTypes(): array
    {
        return array_map(
            static fn (OutboxMessage $message): string => $message->jsonSerialize()['event_type'],
            $this->persisted,
        );
    }

    /**
     * @return list<int>
     */
    public function persistedEventSchemaVersions(): array
    {
        return array_map(
            static fn (OutboxMessage $message): int => $message->jsonSerialize()['event_version'],
            $this->persisted,
        );
    }

    /**
     * @return list<int>
     */
    public function persistedAggregateVersions(): array
    {
        return array_map(
            static fn (OutboxMessage $message): int => $message->jsonSerialize()['aggregate_version'],
            $this->persisted,
        );
    }

    /**
     * @return list<string>
     */
    public function persistedAggregateTypes(): array
    {
        return array_map(
            static fn (OutboxMessage $message): string => $message->jsonSerialize()['aggregate_type'],
            $this->persisted,
        );
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        $this->persisted = [...$this->persisted, ...$this->pending];
        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}

final class FakeAuditRecorder implements AuditRecorder, FakeTransactionalParticipant
{
    /** @var list<AuditRecord> */
    private array $persisted = [];

    /** @var list<AuditRecord> */
    private array $pending = [];

    public ?int $failOnRecordCall = null;

    private int $recordCalls = 0;

    public function record(AuditRecord $auditRecord): void
    {
        $this->recordCalls++;

        if ($this->failOnRecordCall !== null && $this->recordCalls === $this->failOnRecordCall) {
            throw new RuntimeException('Audit recording failed.');
        }

        $this->pending[] = $auditRecord;
    }

    /**
     * @return list<AuditRecord>
     */
    public function persisted(): array
    {
        return $this->persisted;
    }

    public function beginTransaction(): void
    {
        $this->pending = [];
    }

    public function commitTransaction(): void
    {
        $this->persisted = [...$this->persisted, ...$this->pending];
        $this->pending = [];
    }

    public function rollBackTransaction(): void
    {
        $this->pending = [];
    }
}
