<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Error\ErrorCategory;

final readonly class RoleKey
{
    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            throw ApplicationOperationFailure::forCategory(
                ErrorCategory::VALIDATION_ERROR,
                'Role key must not be empty.',
            );
        }

        return new self($normalized);
    }

    public static function organizationOwner(): self
    {
        return self::fromString('organization_owner');
    }

    public function value(): string
    {
        return $this->value;
    }
}
