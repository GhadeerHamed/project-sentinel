<?php

declare(strict_types=1);

namespace Sentinel\IdentityAccess\Domain\ValueObject;

use JsonSerializable;
use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;
use Stringable;

final readonly class PermissionCode implements JsonSerializable, Stringable
{
    private const CODE_PATTERN = '/\A[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+\z/';

    private function __construct(
        private string $value,
    ) {}

    public static function fromString(string $value): self
    {
        $normalized = strtolower(trim($value));

        if (preg_match(self::CODE_PATTERN, $normalized) !== 1) {
            throw InvalidIdentityAccessState::forField('permission_code');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
