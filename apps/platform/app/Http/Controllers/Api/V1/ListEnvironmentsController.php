<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListEnvironmentsRequest;
use App\Http\Resources\EnvironmentResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Environment\Application\Handler\ListEnvironmentsHandler;

final class ListEnvironmentsController extends Controller
{
    public function __invoke(ListEnvironmentsRequest $request, ListEnvironmentsHandler $handler): JsonResponse
    {
        $query = $request->environmentListQuery();
        $environments = $handler->handle($request->projectId(), $request->applicationId(), $query);

        return EnvironmentResource::collection($environments->environments())
            ->additional([
                'pagination' => [
                    'next_cursor' => $environments->nextCursor()?->encode($query->filterHash()),
                    'has_more' => $environments->hasMore(),
                ],
            ])
            ->response();
    }
}
