<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

final readonly class ExpectedVersion
{
    private const MODE_ANY = 'any';

    private const MODE_NONE = 'none';

    private const MODE_EXACT = 'exact';

    private function __construct(
        private string $mode,
        private ?ResourceVersion $value,
    ) {}

    public static function any(): self
    {
        return new self(self::MODE_ANY, null);
    }

    public static function none(): self
    {
        return new self(self::MODE_NONE, null);
    }

    public static function fromInt(int $value): self
    {
        return new self(self::MODE_EXACT, ResourceVersion::fromInt($value));
    }

    public function value(): ?ResourceVersion
    {
        return $this->value;
    }

    public function isAny(): bool
    {
        return $this->mode === self::MODE_ANY;
    }

    public function isNone(): bool
    {
        return $this->mode === self::MODE_NONE;
    }

    public function matches(CurrentVersion $currentVersion): bool
    {
        if ($this->isAny()) {
            return true;
        }

        if ($this->isNone()) {
            return $currentVersion->value() === null;
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
