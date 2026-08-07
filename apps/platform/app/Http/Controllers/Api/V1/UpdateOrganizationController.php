<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrganizationRequest;
use Illuminate\Http\JsonResponse;
use Sentinel\Organization\Application\Command\UpdateOrganizationCommand;
use Sentinel\Organization\Application\Handler\UpdateOrganizationHandler;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class UpdateOrganizationController extends Controller
{
    public function __invoke(UpdateOrganizationRequest $request, UpdateOrganizationHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $result = $handler->handle(new UpdateOrganizationCommand(
            name: $request->validated('name'),
            timezone: $request->validated('timezone'),
            expectedVersion: ExpectedVersion::fromInt($request->expectedVersion()),
        ));

        return response()->json([
            'data' => ['organization_id' => $result->organizationId()->value(), 'name' => $result->name(), 'timezone' => $result->timezone(), 'version' => $result->version()->value()],
            'meta' => ['correlation_id' => $tenantContext->correlationId()->value()],
        ])
            ->header('ETag', sprintf('"%d"', $result->version()->value()))
            ->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
