<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

use Sentinel\Shared\Domain\Error\ApplicationError;
use Sentinel\Shared\Domain\Error\ErrorCategory;

final class IdempotencyKeyConflict extends ApplicationError
{
    public static function forRequest(IdempotencyRequest $request): self
    {
        return new self(
            'The idempotency key was already used with a different request.',
            ErrorCategory::CONFLICT,
            [
                'operation' => $request->operation(),
                'idempotency_key' => $request->key(),
            ],
        );
    }
}
