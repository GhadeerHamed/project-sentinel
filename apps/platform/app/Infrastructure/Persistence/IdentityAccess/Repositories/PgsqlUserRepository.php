<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\IdentityAccess\Repositories;

use App\Infrastructure\Persistence\IdentityAccess\Mappers\IdentityUserMapper;
use App\Infrastructure\Persistence\IdentityAccess\Records\IdentityUserRecord;
use Illuminate\Database\QueryException;
use Sentinel\IdentityAccess\Domain\Entity\User;
use Sentinel\IdentityAccess\Domain\Exception\UserNotFound;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\Shared\Domain\Concurrency\CurrentVersion;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Concurrency\OptimisticConcurrencyConflict;
use Sentinel\Shared\Domain\Identity\IdentityId;

final class PgsqlUserRepository implements UserRepository
{
    public function getByIdentityId(IdentityId $identityId): User
    {
        $record = IdentityUserRecord::query()->whereKey($identityId->value())->first();

        if ($record === null) {
            throw UserNotFound::forIdentity($identityId);
        }

        return IdentityUserMapper::toDomain($record);
    }

    public function save(User $user, ExpectedVersion $expectedVersion): void
    {
        $data = IdentityUserMapper::toPersistence($user);
        $identityId = $user->identityId()->value();

        if ($expectedVersion->isNone()) {
            $existingVersion = IdentityUserRecord::query()
                ->whereKey($identityId)
                ->value('version');

            if ($existingVersion !== null) {
                throw OptimisticConcurrencyConflict::forVersions(
                    $expectedVersion,
                    CurrentVersion::fromInt((int) $existingVersion),
                );
            }

            try {
                IdentityUserRecord::query()->create($data);

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
            $existing = IdentityUserRecord::query()->whereKey($identityId)->exists();

            if ($existing) {
                IdentityUserRecord::query()->whereKey($identityId)->update($data);
            } else {
                IdentityUserRecord::query()->create($data);
            }

            return;
        }

        $expected = $expectedVersion->value();
        $expectedValue = $expected?->value();
        $updated = IdentityUserRecord::query()
            ->whereKey($identityId)
            ->where('version', $expectedValue)
            ->update($data);

        if ($updated === 0) {
            throw OptimisticConcurrencyConflict::forVersions(
                $expectedVersion,
                $this->currentVersion($identityId),
            );
        }
    }

    private function currentVersion(string $identityId): CurrentVersion
    {
        $version = IdentityUserRecord::query()
            ->whereKey($identityId)
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
