<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Project\Queries;

use App\Infrastructure\Persistence\Project\Mappers\ProjectListItemMapper;
use App\Infrastructure\Persistence\Project\Records\ProjectRecord;
use Illuminate\Database\Eloquent\Builder;
use Sentinel\Project\Application\Query\ListProjectsQuery;
use Sentinel\Project\Application\Query\ProjectListQuery;
use Sentinel\Project\Application\ReadModel\ProjectListItem;
use Sentinel\Project\Application\ReadModel\ProjectListResult;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlProjectListQuery implements ProjectListQuery
{
    public function list(OrganizationId $organizationId, ListProjectsQuery $query): ProjectListResult
    {
        $projects = ProjectRecord::query()
            ->select([
                'id',
                'name',
                'slug',
                'description',
                'status',
                'version',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId->value())
            ->where('status', $query->status()->value);

        if ($query->name() !== null) {
            $projects->where('name', $query->name());
        }

        if ($query->slug() !== null) {
            $projects->where('slug', $query->slug());
        }

        if ($query->cursor() !== null) {
            $cursor = $query->cursor();
            $projects->where(static function (Builder $cursorQuery) use ($cursor): void {
                $cursorQuery->where('created_at', '<', $cursor->createdAt())
                    ->orWhere(static function (Builder $sameTimestampQuery) use ($cursor): void {
                        $sameTimestampQuery->where('created_at', $cursor->createdAt())
                            ->where('id', '<', $cursor->id());
                    });
            });
        }

        /** @var list<ProjectListItem> $items */
        $items = $projects
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($query->limit() + 1)
            ->get()
            ->map(static fn (ProjectRecord $record): ProjectListItem => ProjectListItemMapper::fromRecord($record))
            ->all();

        $hasMore = count($items) > $query->limit();
        $page = array_slice($items, 0, $query->limit());
        $nextCursor = $hasMore && $page !== []
            ? CreatedAtIdCursor::fromValues(
                $page[array_key_last($page)]->createdAt(),
                $page[array_key_last($page)]->id(),
            )
            : null;

        return new ProjectListResult($page, $nextCursor);
    }
}
