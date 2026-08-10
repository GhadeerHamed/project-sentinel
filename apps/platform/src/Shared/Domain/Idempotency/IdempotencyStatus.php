<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Idempotency;

enum IdempotencyStatus: string
{
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
}
