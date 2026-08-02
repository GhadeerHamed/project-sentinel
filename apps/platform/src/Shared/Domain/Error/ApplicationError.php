<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Error;

use RuntimeException;
use Throwable;

abstract class ApplicationError extends RuntimeException implements TypedError
{
    /**
     * @param  array<string, scalar|null>  $context
     */
    final protected function __construct(
        string $message,
        private readonly ErrorCategory $category,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    final public function category(): ErrorCategory
    {
        return $this->category;
    }

    final public function context(): array
    {
        return $this->context;
    }
}
