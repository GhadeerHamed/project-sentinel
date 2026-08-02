<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Identifier;

use JsonSerializable;
use Stringable;

abstract readonly class ResourceId implements JsonSerializable, Stringable
{
    private const UUID_V7_PATTERN =
        '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i';

    final protected function __construct(
        private string $value,
    ) {}

    final public static function fromString(string $value): static
    {
        if (preg_match(self::UUID_V7_PATTERN, $value) !== 1) {
            throw InvalidResourceId::forValue($value, static::class);
        }

        return new static(strtolower($value));
    }

    final public function value(): string
    {
        return $this->value;
    }

    final public function equals(ResourceId $other): bool
    {
        return $other::class === static::class
            && $other->value === $this->value;
    }

    final public function jsonSerialize(): string
    {
        return $this->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}
