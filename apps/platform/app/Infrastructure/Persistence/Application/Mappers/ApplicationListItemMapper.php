<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Mappers;

use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;

final class ApplicationListItemMapper
{
    public static function fromRecord(ApplicationRecord $record): ApplicationListItem
    {
        return new ApplicationListItem(
            id: $record->id,
            projectId: $record->project_id,
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            criticality: $record->criticality,
            status: $record->status,
            version: $record->version,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }
}
