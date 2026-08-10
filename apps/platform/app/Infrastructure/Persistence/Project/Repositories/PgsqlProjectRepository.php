<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project\Repositories;

use App\Infrastructure\Persistence\Project\Mappers\ProjectMapper;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Database\QueryException;
use Sentinel\Project\Domain\Entity\Project;
use Sentinel\Project\Domain\Exception\ProjectNotFound;
use Sentinel\Project\Domain\Exception\ProjectSlugConflict;
use Sentinel\Project\Domain\Repository\ProjectRepository;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlProjectRepository implements ProjectRepository
{
    public function getById(ProjectId $projectId, OrganizationId $organizationId): Project
    {
        $record = ProjectRecord::query()
            ->where('id', $projectId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw ProjectNotFound::forId($projectId, $organizationId);
        }

        return ProjectMapper::toDomain($record);
    }

    public function save(Project $project, ExpectedVersion $expectedVersion): void
    {
        if (! $expectedVersion->isNone()) {
            throw new \LogicException('Project persistence currently supports creation only.');
        }

        try {
            ProjectRecord::query()->create(ProjectMapper::toPersistence($project));
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ProjectSlugConflict::forSlug($project->organizationId(), $project->slug());
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? null, ['23505', '23000'], true);
    }
}
