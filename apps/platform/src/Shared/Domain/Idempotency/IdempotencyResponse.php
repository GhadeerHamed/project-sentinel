<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

final readonly class IdempotencyResponse
{
    private function __construct(
        private int $status,
        private IdempotencyResourceReference $resourceReference,
    ) {}

    public static function fromValues(int $status, string $resourceReference): self
    {
        if ($status < 200 || $status > 299) {
            throw InvalidIdempotencyRequest::forField('response_status');
        }

        return new self($status, IdempotencyResourceReference::fromString($resourceReference));
    }

    public function status(): int
    {
        return $this->status;
    }

    public function resourceReference(): IdempotencyResourceReference
    {
        return $this->resourceReference;
    }
}
