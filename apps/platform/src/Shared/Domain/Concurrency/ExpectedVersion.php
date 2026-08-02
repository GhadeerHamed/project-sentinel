<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

final readonly class ExpectedVersion
{
    private function __construct(
        private ?ResourceVersion $value,
    ) {}

    public static function any(): self
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

    public function matches(CurrentVersion $currentVersion): bool
    {
        if ($this->value === null) {
            return true;
        }

        $current = $currentVersion->value();

        return $current !== null && $this->value->equals($current);
    }

    /**
     * @throws OptimisticConcurrencyConflict
     */
    public function assertMatches(CurrentVersion $currentVersion): void
    {
        if (! $this->matches($currentVersion)) {
            throw OptimisticConcurrencyConflict::forVersions($this, $currentVersion);
        }
    }
}
