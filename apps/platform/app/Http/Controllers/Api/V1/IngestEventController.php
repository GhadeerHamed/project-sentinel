<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IngestEventRequest;
use App\Http\Resources\EventResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventCommand;
use Sentinel\Event\Application\Handler\IngestEventHandler;

final class IngestEventController extends Controller
{
    public function __invoke(IngestEventRequest $request, IngestEventHandler $handler): JsonResponse
    {
        $eventSourceIdentity = $request->attributes->get(EventSourceIdentity::REQUEST_ATTRIBUTE);

        if (! $eventSourceIdentity instanceof EventSourceIdentity) {
            abort(401);
        }

        $result = $handler->handle(
            IngestEventCommand::fromValues(
                eventType: $request->validated('event_type'),
                schemaVersion: $request->validated('schema_version'),
                occurredAt: $request->validated('occurred_at'),
                payload: $request->validated('payload'),
                idempotencyKey: $request->validated('idempotency_key'),
                externalEventId: $request->validated('external_event_id'),
                severity: $request->validated('severity'),
                attributes: $request->validated('attributes', []),
                correlationId: $request->validated('correlation_id'),
                causationId: $request->validated('causation_id'),
                traceId: $request->validated('trace_id'),
            ),
            $eventSourceIdentity,
        );
        $correlationId = $result->event()->correlationId()?->value();

        $response = response()->json([
            'data' => new EventResource($result->event())->resolve(),
        ], 202);

        if ($correlationId !== null) {
            $response->header('X-Correlation-ID', $correlationId);
        }

        return $response;
    }
}
