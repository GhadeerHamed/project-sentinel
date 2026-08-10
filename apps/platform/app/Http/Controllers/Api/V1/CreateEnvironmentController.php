<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateEnvironmentRequest;
use App\Http\Resources\EnvironmentResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Environment\Application\Command\CreateEnvironmentCommand;
use Sentinel\Environment\Application\Handler\CreateEnvironmentHandler;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class CreateEnvironmentController extends Controller
{
    public function __invoke(CreateEnvironmentRequest $request, CreateEnvironmentHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $project = $request->route('project');
        $application = $request->route('application');

        if (! is_string($project) || ! is_string($application)) {
            abort(404);
        }

        $result = $handler->handle(CreateEnvironmentCommand::fromValues(
            projectId: $project,
            applicationId: $application,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            environmentType: $request->validated('environment_type'),
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        return response()->json([
            'data' => new EnvironmentResource($result)->resolve(),
            'meta' => ['correlation_id' => $tenantContext->correlationId()->value()],
        ], 201)->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
