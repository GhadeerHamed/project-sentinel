<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentinel\Event\Application\Handler\GetEventHandler;
use Sentinel\Event\Domain\ValueObject\EventId;

final class GetEventController extends Controller
{
    public function __invoke(Request $request, GetEventHandler $handler): JsonResponse
    {
        $event = $request->route('event');
        if (! is_string($event)) {
            abort(404);
        }
        $detail = $handler->handle(EventId::fromString($event));

        return response()->json(['data' => ['id' => $detail->id(), 'type' => 'event', 'attributes' => [...$detail->attributes(), 'payload' => $detail->payload(), 'attributes' => $detail->eventAttributes()]]]);
    }
}
