<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Event\Queries;

use App\Infrastructure\Persistence\Event\Records\EventRecord;
use Illuminate\Database\Eloquent\Builder;
use Sentinel\Event\Application\Query\EventListQuery;
use Sentinel\Event\Application\Query\ListEventsQuery;
use Sentinel\Event\Application\ReadModel\EventListItem;
use Sentinel\Event\Application\ReadModel\EventListResult;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class PgsqlEventListQuery implements EventListQuery
{
    public function list(OrganizationId $organizationId, ListEventsQuery $query): EventListResult
    {
        $events = EventRecord::query()->select(['id', 'project_id', 'application_id', 'environment_id', 'event_source_id', 'external_event_id', 'event_type', 'category', 'severity', 'schema_version', 'occurred_at', 'received_at', 'created_at', 'correlation_id', 'causation_id', 'trace_id'])->where('organization_id', $organizationId->value());
        foreach ($query->filters() as $column => $value) {
            if ($value === null) {
                continue;
            }
            if ($column === 'occurred_from') {
                $events->where('occurred_at', '>=', $value);

                continue;
            }
            if ($column === 'occurred_to') {
                $events->where('occurred_at', '<', $value);

                continue;
            }
            if ($column === 'received_from') {
                $events->where('received_at', '>=', $value);

                continue;
            }
            if ($column === 'received_to') {
                $events->where('received_at', '<', $value);

                continue;
            }
            if (in_array($column, ['project_id', 'application_id', 'environment_id', 'event_source_id', 'event_type', 'category', 'severity'], true)) {
                $events->where($column, $value);
            }
        }
        if ($query->cursorOccurredAt() !== null && $query->cursorId() !== null) {
            $events->where(static function (Builder $builder) use ($query): void {
                $builder->where('occurred_at', '<', $query->cursorOccurredAt())->orWhere(static fn (Builder $same): Builder => $same->where('occurred_at', $query->cursorOccurredAt())->where('id', '<', $query->cursorId()));
            });
        }
        $items = $events->orderByDesc('occurred_at')->orderByDesc('id')->limit($query->limit() + 1)->get()->map(static fn (EventRecord $event): EventListItem => new EventListItem($event->id, ['project_id' => $event->project_id, 'application_id' => $event->application_id, 'environment_id' => $event->environment_id, 'event_source_id' => $event->event_source_id, 'external_event_id' => $event->external_event_id, 'event_type' => $event->event_type, 'category' => $event->category, 'severity' => $event->severity, 'schema_version' => $event->schema_version, 'occurred_at' => $event->occurred_at->format(DATE_ATOM), 'received_at' => $event->received_at->format(DATE_ATOM), 'created_at' => $event->created_at->format(DATE_ATOM), 'correlation_id' => $event->correlation_id, 'causation_id' => $event->causation_id, 'trace_id' => $event->trace_id], $event->occurred_at))->all();
        $hasMore = count($items) > $query->limit();
        $page = array_slice($items, 0, $query->limit());
        $last = $page[array_key_last($page)] ?? null;
        $cursor = $hasMore && $last !== null ? rtrim(strtr(base64_encode(json_encode(['occurred_at' => $last->occurredAt()->format('Y-m-d\\TH:i:s.uP'), 'id' => $last->id(), 'filter_hash' => $query->filterHash()], JSON_THROW_ON_ERROR)), '+/', '-_'), '=') : null;

        return new EventListResult($page, $cursor);
    }
}
