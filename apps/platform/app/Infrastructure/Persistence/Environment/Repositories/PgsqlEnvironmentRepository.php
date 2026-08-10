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
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
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
        $data = EnvironmentMapper::toPersistence($environment);
        $environmentId = $environment->id()->value();
        $organizationId = $environment->organizationId()->value();
        $applicationId = $environment->applicationId()->value();

        if (! $expectedVersion->isNone()) {
            $query = EnvironmentRecord::query()
                ->where('id', $environmentId)
                ->where('organization_id', $organizationId)
                ->where('application_id', $applicationId);

            if ($expectedVersion->isAny()) {
                $query->update($data);

                return;
            }

            $updated = $query
                ->where('version', $expectedVersion->value()?->value())
                ->update($data);

            if ($updated === 0) {
                throw OptimisticConcurrencyConflict::forVersions(
                    $expectedVersion,
                    $this->currentVersion($environmentId, $organizationId, $applicationId),
                );
            }

            return;
        }

        try {
            EnvironmentRecord::query()->create($data);
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

    private function currentVersion(string $environmentId, string $organizationId, string $applicationId): CurrentVersion
    {
        $version = EnvironmentRecord::query()
            ->where('id', $environmentId)
            ->where('organization_id', $organizationId)
            ->where('application_id', $applicationId)
            ->value('version');

        return $version === null ? CurrentVersion::none() : CurrentVersion::fromInt((int) $version);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array($exception->errorInfo[0] ?? null, ['23505', '23000'], true);
    }
}
