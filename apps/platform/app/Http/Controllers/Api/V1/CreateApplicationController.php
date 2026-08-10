<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateApplicationRequest;
use App\Http\Resources\ApplicationResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Application\Application\Command\CreateApplicationCommand;
use Sentinel\Application\Application\Handler\CreateApplicationHandler;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class CreateApplicationController extends Controller
{
    public function __invoke(CreateApplicationRequest $request, CreateApplicationHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $project = $request->route('project');

        if (! is_string($project)) {
            abort(404);
        }

        $result = $handler->handle(CreateApplicationCommand::fromValues(
            projectId: $project,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
            criticality: $request->validated('criticality'),
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        return response()->json([
            'data' => new ApplicationResource($result)->resolve(),
            'meta' => ['correlation_id' => $tenantContext->correlationId()->value()],
        ], 201)->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
