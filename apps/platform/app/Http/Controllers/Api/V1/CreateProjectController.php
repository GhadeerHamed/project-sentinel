<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateProjectRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Project\Application\Command\CreateProjectCommand;
use Sentinel\Project\Application\Handler\CreateProjectHandler;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class CreateProjectController extends Controller
{
    public function __invoke(CreateProjectRequest $request, CreateProjectHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $result = $handler->handle(CreateProjectCommand::fromValues(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        return response()->json([
            'data' => new ProjectResource($result)->resolve(),
            'meta' => ['correlation_id' => $tenantContext->correlationId()->value()],
        ], 201)->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
