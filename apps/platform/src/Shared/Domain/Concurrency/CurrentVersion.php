<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

final readonly class CurrentVersion
{
    private function __construct(
        private ?ResourceVersion $value,
    ) {}

    public static function none(): self
    {
        return new self(null);
    }

    public static function fromInt(int $value): self
    {
        return new self(ResourceVersion::fromInt($value));
    }

    public function value(): ?ResourceVersion
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        if ($this->value === null || $other->value === null) {
            return $this->value === $other->value;
        }

        return $this->value->equals($other->value);
    }
}
