<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Error;

interface TypedError
{
    public function category(): ErrorCategory;

    /**
     * @return array<string, scalar|null>
     */
    public function context(): array;
}
