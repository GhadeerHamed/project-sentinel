<?php

declare(strict_types=1);

namespace Sentinel\Shared\Application\Idempotency;

use Sentinel\Shared\Domain\Idempotency\IdempotencyResponse;

final readonly class IdempotencyExecution
{
    public function __construct(
        private IdempotencyResponse $response,
        private bool $replayed,
    ) {}

    public function response(): IdempotencyResponse
    {
        return $this->response;
    }

    public function wasReplayed(): bool
    {
        return $this->replayed;
    }
}
