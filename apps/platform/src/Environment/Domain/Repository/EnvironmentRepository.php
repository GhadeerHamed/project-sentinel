<?php

declare(strict_types=1);

namespace Sentinel\Environment\Domain\Repository;

use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\Entity\Environment;
use Sentinel\Environment\Domain\Exception\EnvironmentNotFound;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

interface EnvironmentRepository
{
    /** @throws EnvironmentNotFound */
    public function getById(EnvironmentId $environmentId, OrganizationId $organizationId, ApplicationId $applicationId): Environment;

    public function save(Environment $environment, ExpectedVersion $expectedVersion): void;
}
