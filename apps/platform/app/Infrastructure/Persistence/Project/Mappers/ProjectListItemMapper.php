<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project\Mappers;

use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Sentinel\Project\Application\ReadModel\ProjectListItem;

final class ProjectListItemMapper
{
    public static function fromRecord(ProjectRecord $record): ProjectListItem
    {
        return new ProjectListItem(
            id: $record->id,
            name: $record->name,
            slug: $record->slug,
            description: $record->description,
            status: $record->status,
            version: $record->version,
            createdAt: $record->created_at,
            updatedAt: $record->updated_at,
        );
    }
}
