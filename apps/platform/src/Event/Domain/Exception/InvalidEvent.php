<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\Exception;

use Sentinel\Shared\Domain\Error\DomainError;
use Sentinel\Shared\Domain\Error\ErrorCategory;

final class InvalidEvent extends DomainError
{
    public static function forField(string $field): self
    {
        return new self(
            sprintf('Invalid Event field: %s.', $field),
            ErrorCategory::VALIDATION_ERROR,
            ['field' => $field],
        );
    }
}
