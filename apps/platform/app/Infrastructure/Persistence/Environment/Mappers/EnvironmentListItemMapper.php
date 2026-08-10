<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Environment\Mappers;

use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;

final class EnvironmentListItemMapper
{
    public static function fromRecord(EnvironmentRecord $record): EnvironmentListItem
    {
        return new EnvironmentListItem(
            id: $record->id,
            applicationId: $record->application_id,
            name: $record->name,
            slug: $record->slug,
            environmentType: $record->environment_type,
            status: $record->status,
            version: $record->version,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }
}
