<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetEnvironmentRequest;
use App\Http\Resources\EnvironmentResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Environment\Application\Handler\GetEnvironmentHandler;

final class GetEnvironmentController extends Controller
{
    public function __invoke(GetEnvironmentRequest $request, GetEnvironmentHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => new EnvironmentResource($handler->handle(
                $request->projectId(),
                $request->applicationId(),
                $request->environmentId(),
            ))->resolve(),
        ]);
    }
}
