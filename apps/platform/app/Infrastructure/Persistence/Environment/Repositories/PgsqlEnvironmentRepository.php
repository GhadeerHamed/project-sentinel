<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Environment\Repositories;

use App\Infrastructure\Persistence\Environment\Mappers\EnvironmentMapper;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use Illuminate\Database\QueryException;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Domain\Entity\Environment;
use Sentinel\Environment\Domain\Exception\EnvironmentNotFound;
use Sentinel\Environment\Domain\Exception\EnvironmentSlugConflict;
use Sentinel\Environment\Domain\Repository\EnvironmentRepository;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEnvironmentRepository implements EnvironmentRepository
{
    public function getById(EnvironmentId $environmentId, OrganizationId $organizationId, ApplicationId $applicationId): Environment
    {
        $record = EnvironmentRecord::query()
            ->where('id', $environmentId->value())
            ->where('organization_id', $organizationId->value())
            ->where('application_id', $applicationId->value())
            ->first();

        if ($record === null) {
            throw EnvironmentNotFound::forId($environmentId, $organizationId, $applicationId);
        }

        return EnvironmentMapper::toDomain($record);
    }

    public function save(Environment $environment, ExpectedVersion $expectedVersion): void
    {
        if (! $expectedVersion->isNone()) {
            throw new \LogicException('Environment updates are not part of the Create Environment slice.');
        }

        try {
            EnvironmentRecord::query()->create(EnvironmentMapper::toPersistence($environment));
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw EnvironmentSlugConflict::forSlug(
                $environment->organizationId(),
                $environment->applicationId(),
                $environment->slug(),
            );
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? null, ['23505', '23000'], true);
    }
}
