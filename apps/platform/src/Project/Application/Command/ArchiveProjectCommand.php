<?php

declare(strict_types=1);

namespace Sentinel\Project\Application\Command;

use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class ArchiveProjectCommand
{
    public function __construct(
        private ProjectId $projectId,
        private ExpectedVersion $expectedVersion,
    ) {}

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
