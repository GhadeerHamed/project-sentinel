<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Application\Queries;

use App\Infrastructure\Persistence\Application\Mappers\ApplicationListItemMapper;
use App\Infrastructure\Persistence\Application\Records\ApplicationRecord;
use Illuminate\Database\Eloquent\Builder;
use Sentinel\Application\Application\Query\ApplicationListQuery;
use Sentinel\Application\Application\Query\ListApplicationsQuery;
use Sentinel\Application\Application\ReadModel\ApplicationListItem;
use Sentinel\Application\Application\ReadModel\ApplicationListResult;
use Sentinel\Project\Domain\ValueObject\ProjectId;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlApplicationListQuery implements ApplicationListQuery
{
    public function list(OrganizationId $organizationId, ProjectId $projectId, ListApplicationsQuery $query): ApplicationListResult
    {
        $applications = ApplicationRecord::query()
            ->select([
                'id',
                'project_id',
                'name',
                'slug',
                'description',
                'criticality',
                'status',
                'version',
                'created_at',
                'updated_at',
            ])
            ->where('organization_id', $organizationId->value())
            ->where('project_id', $projectId->value())
            ->where('status', $query->status()->value);

        if ($query->name() !== null) {
            $applications->where('name', $query->name());
        }

        if ($query->slug() !== null) {
            $applications->where('slug', $query->slug());
        }

        if ($query->cursor() !== null) {
            $cursor = $query->cursor();
            $applications->where(static function (Builder $cursorQuery) use ($cursor): void {
                $cursorQuery->where('created_at', '<', $cursor->createdAt())
                    ->orWhere(static function (Builder $sameTimestampQuery) use ($cursor): void {
                        $sameTimestampQuery->where('created_at', $cursor->createdAt())
                            ->where('id', '<', $cursor->id());
                    });
            });
        }

        /** @var list<ApplicationListItem> $items */
        $items = $applications
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($query->limit() + 1)
            ->get()
            ->map(static fn (ApplicationRecord $record): ApplicationListItem => ApplicationListItemMapper::fromRecord($record))
            ->all();

        $hasMore = count($items) > $query->limit();
        $page = array_slice($items, 0, $query->limit());
        $nextCursor = $hasMore && $page !== []
            ? CreatedAtIdCursor::fromValues(
                $page[array_key_last($page)]->createdAt(),
                $page[array_key_last($page)]->id(),
            )
            : null;

        return new ApplicationListResult($page, $nextCursor);
    }
}
