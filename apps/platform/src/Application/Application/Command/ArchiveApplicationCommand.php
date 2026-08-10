<?php

declare(strict_types=1);

namespace Sentinel\Application\Application\Command;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class ArchiveApplicationCommand
{
    public function __construct(private ProjectId $projectId, private ApplicationId $applicationId, private ExpectedVersion $expectedVersion) {}

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
