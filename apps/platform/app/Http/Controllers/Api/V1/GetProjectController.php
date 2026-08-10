<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetProjectRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Project\Application\Handler\GetProjectHandler;

final class GetProjectController extends Controller
{
    public function __invoke(GetProjectRequest $request, GetProjectHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => new ProjectResource($handler->handle($request->projectId()))->resolve(),
        ]);
    }
}
