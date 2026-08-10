<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

final readonly class IdempotencyReservation
{
    public function __construct(
        private IdempotencyRecord $record,
        private bool $wasCreated,
    ) {}

    public function record(): IdempotencyRecord
    {
        return $this->record;
    }

    public function wasCreated(): bool
    {
        return $this->wasCreated;
    }
}
