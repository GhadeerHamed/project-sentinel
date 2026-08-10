<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Repositories;

use App\Infrastructure\Persistence\Application\Mappers\ApplicationMapper;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use Illuminate\Database\QueryException;
use Sentinel\Application\Domain\Entity\Application;
use Sentinel\Application\Domain\Exception\ApplicationNotFound;
use Sentinel\Application\Domain\Exception\ApplicationSlugConflict;
use Sentinel\Application\Domain\Repository\ApplicationRepository;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlApplicationRepository implements ApplicationRepository
{
    public function getById(ApplicationId $applicationId, OrganizationId $organizationId, ProjectId $projectId): Application
    {
        $record = ApplicationRecord::query()
            ->where('id', $applicationId->value())
            ->where('organization_id', $organizationId->value())
            ->where('project_id', $projectId->value())
            ->first();

        if ($record === null) {
            throw ApplicationNotFound::forId($applicationId, $organizationId, $projectId);
        }

        return ApplicationMapper::toDomain($record);
    }

    public function save(Application $application, ExpectedVersion $expectedVersion): void
    {
        if (! $expectedVersion->isNone()) {
            throw new \LogicException('Application updates are not implemented.');
        }

        try {
            ApplicationRecord::query()->create(ApplicationMapper::toPersistence($application));
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ApplicationSlugConflict::forSlug(
                $application->organizationId(),
                $application->projectId(),
                $application->slug(),
            );
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? null, ['23505', '23000'], true);
    }
}
