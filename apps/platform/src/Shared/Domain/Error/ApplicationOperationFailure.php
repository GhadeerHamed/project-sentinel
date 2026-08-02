<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Error;

final class ApplicationOperationFailure extends ApplicationError
{
    /**
     * @param  array<string, scalar|null>  $context
     */
    public static function forCategory(
        ErrorCategory $category,
        string $message,
        array $context = [],
    ): self {
        return new self($message, $category, $context);
    }
}
