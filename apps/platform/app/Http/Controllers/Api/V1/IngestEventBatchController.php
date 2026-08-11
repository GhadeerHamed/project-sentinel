<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IngestEventBatchRequest;
use App\Http\Resources\EventResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventBatchCommand;
use Sentinel\Event\Application\Command\IngestEventBatchItem;
use Sentinel\Event\Application\Handler\IngestEventBatchHandler;

final class IngestEventBatchController extends Controller
{
    public function __invoke(IngestEventBatchRequest $request, IngestEventBatchHandler $handler): JsonResponse
    {
        $source = $request->attributes->get(EventSourceIdentity::REQUEST_ATTRIBUTE);
        if (! $source instanceof EventSourceIdentity) {
            abort(401);
        }
        $items = array_map(static fn (array $event): IngestEventBatchItem => new IngestEventBatchItem($event['event_type'], $event['schema_version'], $event['occurred_at'], $event['payload'], $event['external_event_id'] ?? null, $event['severity'] ?? null, $event['attributes'] ?? [], $event['correlation_id'] ?? null, $event['causation_id'] ?? null, $event['trace_id'] ?? null), $request->validated('events'));
        $result = $handler->handle(IngestEventBatchCommand::fromItems($items, $request->validated('idempotency_key')), $source);

        return response()->json(['data' => array_map(static fn ($event): array => (new EventResource($event))->resolve(), $result->events())], 202);
    }
}
