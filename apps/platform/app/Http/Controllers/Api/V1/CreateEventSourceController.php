<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateEventSourceRequest;
use App\Http\Resources\EventSourceResource;
use Illuminate\Http\JsonResponse;
use Sentinel\Event\Application\Command\RegisterEventSourceCommand;
use Sentinel\Event\Application\Handler\RegisterEventSourceHandler;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class CreateEventSourceController extends Controller
{
    public function __invoke(CreateEventSourceRequest $request, RegisterEventSourceHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $project = $request->route('project');
        $application = $request->route('application');
        $environment = $request->route('environment');

        if (! is_string($project) || ! is_string($application) || ! is_string($environment)) {
            abort(404);
        }

        $result = $handler->handle(RegisterEventSourceCommand::fromValues(
            projectId: $project,
            applicationId: $application,
            environmentId: $environment,
            name: $request->validated('name'),
            idempotencyKey: $request->validated('idempotency_key'),
        ));

        $meta = ['correlation_id' => $tenantContext->correlationId()->value()];
        $issuedCredential = $result->issuedCredential();

        if ($issuedCredential !== null) {
            $meta['issued_credential'] = [
                'id' => $issuedCredential->credential()->id()->value(),
                'bearer_token' => $issuedCredential->bearerCredential(),
                'shown_once' => true,
            ];
        }

        return response()->json([
            'data' => new EventSourceResource($result->eventSource())->resolve(),
            'meta' => $meta,
        ], 201)->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
