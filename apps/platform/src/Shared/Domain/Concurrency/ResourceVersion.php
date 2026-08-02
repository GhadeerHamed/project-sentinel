<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

final readonly class ResourceVersion
{
    private function __construct(
        private int $value,
    ) {}

    public static function fromInt(int $value): self
    {
        if ($value < 1) {
            throw InvalidResourceVersion::forValue($value);
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function next(): self
    {
        return self::fromInt($this->value + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
