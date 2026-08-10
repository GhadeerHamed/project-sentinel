<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Command;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class ArchiveEnvironmentCommand
{
    public function __construct(
        private ProjectId $projectId,
        private ApplicationId $applicationId,
        private EnvironmentId $environmentId,
        private ExpectedVersion $expectedVersion,
    ) {}

    public function projectId(): ProjectId
    {
        return $this->projectId;
    }

    public function applicationId(): ApplicationId
    {
        return $this->applicationId;
    }

    public function environmentId(): EnvironmentId
    {
        return $this->environmentId;
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
