<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListEventsRequest;
use Illuminate\Http\JsonResponse;
use Sentinel\Event\Application\Handler\ListEventsHandler;

final class ListEventsController extends Controller
{
    public function __invoke(ListEventsRequest $request, ListEventsHandler $handler): JsonResponse
    {
        $result = $handler->handle($request->queryModel());

        return response()->json(['data' => array_map(static fn ($event): array => ['id' => $event->id(), 'type' => 'event', 'attributes' => $event->attributes()], $result->events()), 'pagination' => ['next_cursor' => $result->nextCursor(), 'has_more' => $result->hasMore()]]);
    }
}
