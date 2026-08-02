<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Concurrency;

use RuntimeException;

final class OptimisticConcurrencyConflict extends RuntimeException implements ConcurrencyConflict
{
    private function __construct(
        private readonly ExpectedVersion $expectedVersion,
        private readonly CurrentVersion $currentVersion,
    ) {
        parent::__construct(sprintf(
            'Optimistic concurrency conflict. Expected version: %s. Current version: %s.',
            self::format($expectedVersion),
            self::formatCurrent($currentVersion),
        ));
    }

    public static function forVersions(ExpectedVersion $expectedVersion, CurrentVersion $currentVersion): self
    {
        return new self($expectedVersion, $currentVersion);
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }

    public function currentVersion(): CurrentVersion
    {
        return $this->currentVersion;
    }

    private static function format(ExpectedVersion $expectedVersion): string
    {
        $value = $expectedVersion->value();

        return $value === null ? 'any' : (string) $value->value();
    }

    private static function formatCurrent(CurrentVersion $currentVersion): string
    {
        $value = $currentVersion->value();

        return $value === null ? 'none' : (string) $value->value();
    }
}
