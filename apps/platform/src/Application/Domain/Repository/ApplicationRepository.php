<?php

declare(strict_types=1);

namespace Sentinel\Application\Domain\Repository;

use Sentinel\Application\Domain\Entity\Application;
use Sentinel\Application\Domain\Exception\ApplicationNotFound;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface ApplicationRepository
{
    /** @throws ApplicationNotFound */
    public function getById(ApplicationId $applicationId, OrganizationId $organizationId, ProjectId $projectId): Application;

    public function save(Application $application, ExpectedVersion $expectedVersion): void;
}
