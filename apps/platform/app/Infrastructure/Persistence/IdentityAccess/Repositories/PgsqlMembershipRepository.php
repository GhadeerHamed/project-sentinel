<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use App\Infrastructure\Persistence\IdentityAccess\Mappers\MembershipMapper;
use App\Infrastructure\Persistence\IdentityAccess\Records\MembershipRecord;
use Illuminate\Database\QueryException;
use Sentinel\IdentityAccess\Domain\Entity\Membership;
use Sentinel\IdentityAccess\Domain\Exception\MembershipNotFound;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlMembershipRepository implements MembershipRepository
{
    public function getById(MembershipId $membershipId, OrganizationId $organizationId): Membership
    {
        $record = MembershipRecord::query()
            ->whereKey($membershipId->value())
            ->where('organization_id', $organizationId->value())
            ->first();

        if ($record === null) {
            throw MembershipNotFound::forId($membershipId, $organizationId);
        }

        return MembershipMapper::toDomain($record);
    }

    public function getByIdentity(OrganizationId $organizationId, IdentityId $identityId): Membership
    {
        $record = MembershipRecord::query()
            ->where('organization_id', $organizationId->value())
            ->where('user_identity_id', $identityId->value())
            ->first();

        if ($record === null) {
            throw MembershipNotFound::forIdentity($identityId, $organizationId);
        }

        return MembershipMapper::toDomain($record);
    }

    public function save(Membership $membership, ExpectedVersion $expectedVersion): void
    {
        $data = MembershipMapper::toPersistence($membership);
        $membershipId = $membership->id()->value();

        if ($expectedVersion->isNone()) {
            $existingVersion = MembershipRecord::query()
                ->whereKey($membershipId)
                ->value('version');

            if ($existingVersion !== null) {
                throw OptimisticConcurrencyConflict::forVersions(
                    $expectedVersion,
                    CurrentVersion::fromInt((int) $existingVersion),
                );
            }

            try {
                MembershipRecord::query()->create($data);

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
            $existing = MembershipRecord::query()->whereKey($membershipId)->exists();

            if ($existing) {
                MembershipRecord::query()->whereKey($membershipId)->update($data);
            } else {
                MembershipRecord::query()->create($data);
            }

            return;
        }

        $expected = $expectedVersion->value();
        $expectedValue = $expected?->value();
        $updated = MembershipRecord::query()
            ->whereKey($membershipId)
            ->where('version', $expectedValue)
            ->update($data);

        if ($updated === 0) {
            throw OptimisticConcurrencyConflict::forVersions(
                $expectedVersion,
                $this->currentVersion($membershipId),
            );
        }
    }

    private function currentVersion(string $membershipId): CurrentVersion
    {
        $version = MembershipRecord::query()
            ->whereKey($membershipId)
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
