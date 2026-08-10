<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\ReadModel;

use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;

final readonly class ApplicationListResult
{
    /** @param list<ApplicationListItem> $applications */
    public function __construct(
        private array $applications,
        private ?CreatedAtIdCursor $nextCursor,
    ) {}

    /** @return list<ApplicationListItem> */
    public function applications(): array
    {
        return $this->applications;
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
