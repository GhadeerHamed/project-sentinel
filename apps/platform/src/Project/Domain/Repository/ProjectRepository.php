<?php

declare(strict_types=1);

namespace Sentinel\Project\Domain\Repository;

use Sentinel\Project\Domain\Entity\Project;
use Sentinel\Project\Domain\Exception\ProjectNotFound;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ProjectRepository
{
    /** @throws ProjectNotFound */
    public function getById(ProjectId $projectId, OrganizationId $organizationId): Project;

    public function save(Project $project, ExpectedVersion $expectedVersion): void;
}
