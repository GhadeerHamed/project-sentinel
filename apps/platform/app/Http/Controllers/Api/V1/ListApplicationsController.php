<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListApplicationsRequest;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Application\Application\Handler\ListApplicationsHandler;

final class ListApplicationsController extends Controller
{
    public function __invoke(ListApplicationsRequest $request, ListApplicationsHandler $handler): JsonResponse
    {
        $query = $request->applicationListQuery();
        $applications = $handler->handle($request->projectId(), $query);

        return ApplicationResource::collection($applications->applications())
            ->additional([
                'pagination' => [
                    'next_cursor' => $applications->nextCursor()?->encode($query->filterHash()),
                    'has_more' => $applications->hasMore(),
                ],
            ])
            ->response();
    }
}
