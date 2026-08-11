<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Idempotency;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Application\Idempotency\IdempotentOperation;
use Sentinel\Shared\Domain\Idempotency\IdempotencyKeyConflict;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRecord;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRecordId;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyReservation;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final class IdempotentOperationTest extends TestCase
{
    #[Test]
    public function it_replays_an_opaque_ordered_result_payload(): void
    {
        $records = new InMemoryIdempotencyRepository;
        $operation = $this->operation($records);
        $request = $this->request(['batch' => 'ordered-events']);
        $payload = ['event_ids' => ['0198f3b6-48ae-7a45-9192-6f8029df5f30', '0198f3b6-48ae-7a45-9192-6f8029df5f30']];

        $first = $operation->execute($request, static fn (DateTimeImmutable $at): IdempotencyResponse => IdempotencyResponse::fromResultPayload(202, $payload));
        $second = $operation->execute($request, static fn (DateTimeImmutable $at): IdempotencyResponse => self::fail('A replay must not execute.'));

        self::assertFalse($first->wasReplayed());
        self::assertTrue($second->wasReplayed());
        self::assertSame($payload, $second->response()->resultPayload());
    }

    #[Test]
    public function it_replays_a_completed_request_without_executing_its_business_operation_again(): void
    {
        $records = new InMemoryIdempotencyRepository;
        $operation = $this->operation($records);
        $request = $this->request(['name' => 'Commerce Platform', 'slug' => 'commerce-platform', 'description' => null]);
        $executions = 0;

        $first = $operation->execute($request, function (DateTimeImmutable $at) use (&$executions): IdempotencyResponse {
            $executions++;

            return IdempotencyResponse::fromValues(201, '0198f3b6-48ae-7a45-9192-6f8029df5f30');
        });
        $second = $operation->execute($request, function (DateTimeImmutable $at) use (&$executions): IdempotencyResponse {
            $executions++;

            return IdempotencyResponse::fromValues(201, '0198f3b6-48ae-7a45-9192-6f8029df5f31');
        });

        self::assertFalse($first->wasReplayed());
        self::assertTrue($second->wasReplayed());
        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f30', $second->response()->resourceReference()->value());
        self::assertSame(1, $executions);
    }

    #[Test]
    public function it_rejects_reuse_of_a_key_with_a_different_normalized_payload(): void
    {
        $records = new InMemoryIdempotencyRepository;
        $operation = $this->operation($records);
        $firstRequest = $this->request(['name' => 'Commerce Platform', 'slug' => 'commerce-platform', 'description' => null]);
        $conflictingRequest = $this->request(['name' => 'Payments Platform', 'slug' => 'payments-platform', 'description' => null]);

        $operation->execute($firstRequest, static fn (DateTimeImmutable $at): IdempotencyResponse => IdempotencyResponse::fromValues(201, '0198f3b6-48ae-7a45-9192-6f8029df5f32'));

        $this->expectException(IdempotencyKeyConflict::class);
        $operation->execute($conflictingRequest, static fn (DateTimeImmutable $at): IdempotencyResponse => IdempotencyResponse::fromValues(201, '0198f3b6-48ae-7a45-9192-6f8029df5f33'));
    }

    /** @param array<string, scalar|null> $payload */
    private function request(array $payload): IdempotencyRequest
    {
        return IdempotencyRequest::fromNormalizedPayload(
            OrganizationId::fromString('0198f3b6-48ae-7a45-9192-6f8029df5f34'),
            IdentityId::fromString('0198f3b6-48ae-7a45-9192-6f8029df5f35'),
            'project.create',
            'project-create-key',
            $payload,
        );
    }

    private function operation(InMemoryIdempotencyRepository $records): IdempotentOperation
    {
        return new IdempotentOperation(
            new ImmediateTransactionManager,
            $records,
            new SequenceUuidGenerator([
                '0198f3b6-48ae-7a45-9192-6f8029df5f36',
                '0198f3b6-48ae-7a45-9192-6f8029df5f37',
                '0198f3b6-48ae-7a45-9192-6f8029df5f38',
            ]),
            new FixedClock(new DateTimeImmutable('2026-08-10T12:00:00+00:00')),
        );
    }
}

final class InMemoryIdempotencyRepository implements IdempotencyRepository
{
    /** @var array<string, array{request: IdempotencyRequest, record: IdempotencyRecord}> */
    private array $records = [];

    public function reserve(IdempotencyRequest $request, IdempotencyRecordId $recordId, DateTimeImmutable $at): IdempotencyReservation
    {
        $scope = $this->scope($request);

        if (isset($this->records[$scope])) {
            return new IdempotencyReservation($this->records[$scope]['record'], false);
        }

        $record = IdempotencyRecord::processing($recordId, $request);
        $this->records[$scope] = ['request' => $request, 'record' => $record];

        return new IdempotencyReservation($record, true);
    }

    public function complete(IdempotencyRecordId $recordId, IdempotencyResponse $response, DateTimeImmutable $completedAt): void
    {
        foreach ($this->records as $scope => $stored) {
            if ($stored['record']->id()->equals($recordId)) {
                $request = $stored['request'];
                $this->records[$scope]['record'] = IdempotencyRecord::fromValues(
                    id: $recordId->value(),
                    organizationId: $request->organizationId()->value(),
                    identityId: $request->identityId()->value(),
                    operation: $request->operation(),
                    key: $request->key(),
                    requestHash: $request->requestHash(),
                    status: 'completed',
                    responseStatus: $response->status(),
                    responseReference: $response->resourceReferenceOrNull()?->value(),
                    resultPayload: $response->resultPayload(),
                    createdAt: $completedAt,
                    completedAt: $completedAt,
                );

                return;
            }
        }

        throw new \RuntimeException('Reserved idempotency record was not found.');
    }

    private function scope(IdempotencyRequest $request): string
    {
        return implode(':', [
            $request->organizationId()->value(),
            $request->identityId()->value(),
            $request->operation(),
            $request->key(),
        ]);
    }
}

final class ImmediateTransactionManager implements TransactionManager
{
    public function begin(): void {}

    public function commit(): void {}

    public function rollBack(): void {}

    public function transactional(callable $operation): mixed
    {
        return $operation();
    }
}

final class SequenceUuidGenerator implements UuidV7Generator
{
    /** @param list<string> $identifiers */
    public function __construct(private array $identifiers) {}

    private int $position = 0;

    public function generate(): string
    {
        $identifier = $this->identifiers[$this->position] ?? null;
        $this->position++;

        if ($identifier === null) {
            throw new \RuntimeException('No UUID remains in the test sequence.');
        }

        return $identifier;
    }
}

final readonly class FixedClock implements Clock
{
    public function __construct(private DateTimeImmutable $now) {}

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
