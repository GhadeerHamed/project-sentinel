<?php

declare(strict_types=1);

namespace Tests\Application\Organization;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Entity\User;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Exception\UserNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
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
    public function it_creates_organization_initial_membership_outbox_and_audit_atomically(): void
    {
        $fixture = $this->fixture();

        $result = $fixture->handler->handle($fixture->command);

        self::assertInstanceOf(CreateOrganizationResult::class, $result);
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c075001', $result->organizationId()->value());
        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c075002', $result->creatorMembershipId()->value());

        self::assertCount(1, $fixture->organizationRepository->persisted());
        self::assertCount(1, $fixture->membershipRepository->persisted());
        self::assertSame('active', array_values($fixture->membershipRepository->persisted())[0]->status()->value);
        self::assertSame(2, array_values($fixture->membershipRepository->persisted())[0]->version()->value());

        self::assertSame(
            ['organization.created', 'membership.invited', 'membership.activated'],
            $fixture->outboxStore->persistedEventTypes(),
        );
        self::assertSame(
            [
                '01890f7a-5b7c-7cc0-98c4-dc0c0c075006',
                '01890f7a-5b7c-7cc0-98c4-dc0c0c075007',
                '01890f7a-5b7c-7cc0-98c4-dc0c0c075008',
            ],
            $fixture->outboxStore->persistedMessageIds(),
        );

        self::assertCount(1, $fixture->auditRecorder->persisted());
        self::assertSame('organization.create', $fixture->auditRecorder->persisted()[0]->action());
        self::assertSame(
            '2026-08-02T16:00:00+00:00',
            $fixture->auditRecorder->persisted()[0]->occurredAt()->format(DATE_ATOM),
        );

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
                self::assertCount(0, $fixture->outboxStore->persisted());
                self::assertCount(0, $fixture->auditRecorder->persisted());
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
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
        }
    }

    #[Test]
    public function transaction_failure_rolls_back_all_side_effects(): void
    {
        $fixture = $this->fixture();
        $fixture->transactionManager->failAfterOperation = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
            self::assertCount(0, $fixture->outboxStore->persisted());
            self::assertCount(0, $fixture->auditRecorder->persisted());
            self::assertSame(1, $fixture->transactionManager->rollBackCount);
        }
    }

    #[Test]
    public function repository_failure_rolls_back_entire_use_case(): void
    {
        $fixture = $this->fixture();
        $fixture->membershipRepository->failOnSave = true;

        $this->expectException(RuntimeException::class);

        try {
            $fixture->handler->handle($fixture->command);
        } finally {
            self::assertCount(0, $fixture->organizationRepository->persisted());
            self::assertCount(0, $fixture->membershipRepository->persisted());
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
        $outboxStore = new FakeOutboxMessageStore;
        $auditRecorder = new FakeAuditRecorder;

        $transactionManager->register($organizationRepository);
        $transactionManager->register($membershipRepository);
        $transactionManager->register($outboxStore);
        $transactionManager->register($auditRecorder);

        $handler = new CreateOrganizationHandler(
            userRepository: $userRepository,
            organizationRepository: $organizationRepository,
            membershipRepository: $membershipRepository,
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
            creatorIdentityType: 'user',
            correlationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c075010',
            causationId: '01890f7a-5b7c-7cc0-98c4-dc0c0c075011',
        );

        return new CreateOrganizationFixture(
            handler: $handler,
            command: $command,
            creatorIdentityId: $creatorIdentityId,
            transactionManager: $transactionManager,
            organizationRepository: $organizationRepository,
            membershipRepository: $membershipRepository,
            outboxStore: $outboxStore,
            auditRecorder: $auditRecorder,
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
        public FakeOutboxMessageStore $outboxStore,
        public FakeAuditRecorder $auditRecorder,
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

final readonly class FakeOrganizationCreationPolicy implements OrganizationCreationPolicy
{
    public function __construct(
        private bool $allows,
    ) {}

    public function allows(IdentityReference $creator): bool
    {
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
     * @return list<string>
     */
    public function persistedMessageIds(): array
    {
        return array_map(
            static fn (OutboxMessage $message): string => $message->jsonSerialize()['id'],
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

    public bool $failOnRecord = false;

    public function record(AuditRecord $auditRecord): void
    {
        if ($this->failOnRecord) {
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
