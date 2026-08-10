<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProjectsRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Project\Application\Handler\ListProjectsHandler;

final class ListProjectsController extends Controller
{
    public function __invoke(ListProjectsRequest $request, ListProjectsHandler $handler): JsonResponse
    {
        $query = $request->projectListQuery();
        $projects = $handler->handle($query);

        return ProjectResource::collection($projects->projects())
            ->additional([
                'pagination' => [
                    'next_cursor' => $projects->nextCursor()?->encode($query->filterHash()),
                    'has_more' => $projects->hasMore(),
                ],
            ])
            ->response();
    }
}
