<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Environment\Queries;

use App\Infrastructure\Persistence\Environment\Mappers\EnvironmentListItemMapper;
use App\Infrastructure\Persistence\Environment\Records\EnvironmentRecord;
use Illuminate\Database\Eloquent\Builder;
use Sentinel\Application\Domain\ValueObject\ApplicationId;
use Sentinel\Environment\Application\Query\EnvironmentListQuery;
use Sentinel\Environment\Application\Query\ListEnvironmentsQuery;
use Sentinel\Environment\Application\ReadModel\EnvironmentListItem;
use Sentinel\Environment\Application\ReadModel\EnvironmentListResult;
use Sentinel\Shared\Application\Pagination\CreatedAtIdCursor;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEnvironmentListQuery implements EnvironmentListQuery
{
    public function list(OrganizationId $organizationId, ApplicationId $applicationId, ListEnvironmentsQuery $query): EnvironmentListResult
    {
        $environments = EnvironmentRecord::query()
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
            ->where('status', $query->status()->value);

        if ($query->environmentType() !== null) {
            $environments->where('environment_type', $query->environmentType()->value);
        }

        if ($query->slug() !== null) {
            $environments->where('slug', $query->slug());
        }

        if ($query->cursor() !== null) {
            $cursor = $query->cursor();
            $environments->where(static function (Builder $cursorQuery) use ($cursor): void {
                $cursorQuery->where('created_at', '<', $cursor->createdAt())
                    ->orWhere(static function (Builder $sameTimestampQuery) use ($cursor): void {
                        $sameTimestampQuery->where('created_at', $cursor->createdAt())
                            ->where('id', '<', $cursor->id());
                    });
            });
        }

        /** @var list<EnvironmentListItem> $items */
        $items = $environments
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($query->limit() + 1)
            ->get()
            ->map(static fn (EnvironmentRecord $record): EnvironmentListItem => EnvironmentListItemMapper::fromRecord($record))
            ->all();

        $hasMore = count($items) > $query->limit();
        $page = array_slice($items, 0, $query->limit());
        $nextCursor = $hasMore && $page !== []
            ? CreatedAtIdCursor::fromValues(
                $page[array_key_last($page)]->createdAt(),
                $page[array_key_last($page)]->id(),
            )
            : null;

        return new EnvironmentListResult($page, $nextCursor);
    }
}
