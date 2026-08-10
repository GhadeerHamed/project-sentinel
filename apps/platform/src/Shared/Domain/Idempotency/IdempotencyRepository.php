<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use DateTimeImmutable;

interface IdempotencyRepository
{
    /**
     * Reserves the request scope and locks any existing record for the enclosing transaction.
     */
    public function reserve(IdempotencyRequest $request, IdempotencyRecordId $recordId, DateTimeImmutable $at): IdempotencyReservation;

    public function complete(IdempotencyRecordId $recordId, IdempotencyResponse $response, DateTimeImmutable $completedAt): void;
}
