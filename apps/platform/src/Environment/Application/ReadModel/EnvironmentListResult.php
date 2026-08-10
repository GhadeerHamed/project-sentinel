<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\ReadModel;

use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final readonly class EnvironmentListResult
{
    /** @param list<EnvironmentListItem> $environments */
    public function __construct(
        private array $environments,
        private ?CreatedAtIdCursor $nextCursor,
    ) {}

    /** @return list<EnvironmentListItem> */
    public function environments(): array
    {
        return $this->environments;
    }

    public function nextCursor(): ?CreatedAtIdCursor
    {
        return $this->nextCursor;
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }
}
