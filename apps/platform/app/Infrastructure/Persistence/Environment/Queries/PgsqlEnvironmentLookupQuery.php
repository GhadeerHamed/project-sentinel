<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Environment\Queries;

use App\Infrastructure\Persistence\Environment\Mappers\EnvironmentListItemMapper;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Query\EnvironmentLookupQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Domain\Exception\EnvironmentNotFound;
use Sentinel\Environment\Domain\ValueObject\EnvironmentId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEnvironmentLookupQuery implements EnvironmentLookupQuery
{
    public function get(
        OrganizationId $organizationId,
        ApplicationId $applicationId,
        EnvironmentId $environmentId,
    ): EnvironmentListItem {
        $environment = EnvironmentRecord::query()
            ->select([
                'id',
                'application_id',
                'name',
                'slug',
                'environment_type',
                'status',
                'version',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId->value())
            ->where('application_id', $applicationId->value())
            ->where('id', $environmentId->value())
            ->first();

        if ($environment === null) {
            throw EnvironmentNotFound::forId($environmentId, $organizationId, $applicationId);
        }

        return EnvironmentListItemMapper::fromRecord($environment);
    }
}
