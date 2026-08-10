<?php

declare(strict_types=1);

namespace Sentinel\Shared\Application\Idempotency;

use Closure;
use DateTimeImmutable;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;
use Sentinel\Shared\Domain\Idempotency\IdempotencyKeyConflict;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRecordId;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRepository;
use Sentinel\Shared\Domain\Idempotency\IdempotencyRequest;
use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Time\Clock;
use Sentinel\Shared\Domain\Transaction\TransactionManager;

final readonly class IdempotentOperation
{
    public function __construct(
        private TransactionManager $transactions,
        private IdempotencyRepository $records,
        private UuidV7Generator $uuidGenerator,
        private Clock $clock,
    ) {}

    /**
     * @param  Closure(DateTimeImmutable): IdempotencyResponse  $operation
     */
    public function execute(IdempotencyRequest $request, Closure $operation): IdempotencyExecution
    {
        return $this->transactions->transactional(function () use ($request, $operation): IdempotencyExecution {
            $now = $this->clock->now();
            $reservation = $this->records->reserve(
                $request,
                IdempotencyRecordId::fromString($this->uuidGenerator->generate()),
                $now,
            );
            $record = $reservation->record();

            if ($record->requestHash() !== $request->requestHash()) {
                throw IdempotencyKeyConflict::forRequest($request);
            }

            if ($record->isCompleted()) {
                return new IdempotencyExecution($record->response(), true);
            }

            if (! $reservation->wasCreated()) {
                throw ApplicationOperationFailure::forCategory(
                    ErrorCategory::CONFLICT,
                    'An idempotent operation is already in progress.',
                    ['operation' => $request->operation()],
                );
            }

            $response = $operation($now);
            $this->records->complete($record->id(), $response, $now);

            return new IdempotencyExecution($response, false);
        });
    }
}
