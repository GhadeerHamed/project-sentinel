<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Organization\Repositories;

use App\Infrastructure\Persistence\Organization\Mappers\OrganizationMapper;
use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Database\QueryException;
use Sentinel\Organization\Domain\Entity\Organization;
use Sentinel\Organization\Domain\Exception\OrganizationNotFound;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlOrganizationRepository implements OrganizationRepository
{
    public function accessibleTo(IdentityId $identityId): array
    {
        return OrganizationRecord::query()->select('organizations.*')->join('memberships', 'memberships.organization_id', '=', 'organizations.id')
            ->where('memberships.user_identity_id', $identityId->value())->where('memberships.status', 'active')->orderBy('organizations.created_at')
            ->get()->map(static fn (OrganizationRecord $record): Organization => OrganizationMapper::toDomain($record))->all();
    }

    public function getById(OrganizationId $organizationId): Organization
    {
        $record = OrganizationRecord::query()->whereKey($organizationId->value())->first();

        if ($record === null) {
            throw OrganizationNotFound::forId($organizationId);
        }

        return OrganizationMapper::toDomain($record);
    }

    public function save(Organization $organization, ExpectedVersion $expectedVersion): void
    {
        $data = OrganizationMapper::toPersistence($organization);
        $organizationId = $organization->id()->value();

        if ($expectedVersion->isNone()) {
            $existingVersion = OrganizationRecord::query()
                ->whereKey($organizationId)
                ->value('version');

            if ($existingVersion !== null) {
                throw OptimisticConcurrencyConflict::forVersions(
                    $expectedVersion,
                    CurrentVersion::fromInt((int) $existingVersion),
                );
            }

            try {
                OrganizationRecord::query()->create($data);

                return;
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                throw OptimisticConcurrencyConflict::forVersions(
                    $expectedVersion,
                    CurrentVersion::none(),
                );
            }
        }

        if ($expectedVersion->isAny()) {
            $existing = OrganizationRecord::query()->whereKey($organizationId)->exists();

            if ($existing) {
                OrganizationRecord::query()->whereKey($organizationId)->update($data);
            } else {
                OrganizationRecord::query()->create($data);
            }

            return;
        }

        $expected = $expectedVersion->value();
        $expectedValue = $expected?->value();
        $updated = OrganizationRecord::query()
            ->whereKey($organizationId)
            ->where('version', $expectedValue)
            ->update($data);

        if ($updated === 0) {
            throw OptimisticConcurrencyConflict::forVersions(
                $expectedVersion,
                $this->currentVersion($organizationId),
            );
        }
    }

    private function currentVersion(string $organizationId): CurrentVersion
    {
        $version = OrganizationRecord::query()
            ->whereKey($organizationId)
            ->value('version');

        if ($version === null) {
            return CurrentVersion::none();
        }

        return CurrentVersion::fromInt((int) $version);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return in_array($sqlState, ['23505', '23000'], true);
    }
}
