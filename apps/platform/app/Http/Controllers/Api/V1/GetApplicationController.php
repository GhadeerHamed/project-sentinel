<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GetApplicationRequest;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Application\Application\Handler\GetApplicationHandler;

final class GetApplicationController extends Controller
{
    public function __invoke(GetApplicationRequest $request, GetApplicationHandler $handler): JsonResponse
    {
        return response()->json([
            'data' => new ApplicationResource($handler->handle($request->projectId(), $request->applicationId()))->resolve(),
        ]);
    }
}
