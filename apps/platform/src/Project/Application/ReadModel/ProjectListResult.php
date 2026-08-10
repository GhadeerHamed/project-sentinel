<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\ReadModel;

use Sentinel\Project\Application\Query\ProjectListCursor;

final readonly class ProjectListResult
{
    /** @param list<ProjectListItem> $projects */
    public function __construct(
        private array $projects,
        private ?ProjectListCursor $nextCursor,
    ) {}

    /** @return list<ProjectListItem> */
    public function projects(): array
    {
        return $this->projects;
    }

    public function nextCursor(): ?ProjectListCursor
    {
        return $this->nextCursor;
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }
}
