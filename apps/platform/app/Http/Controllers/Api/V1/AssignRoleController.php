<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AssignRoleRequest;
use Illuminate\Http\JsonResponse;
use Sentinel\IdentityAccess\Application\Command\AssignRoleCommand;
use Sentinel\IdentityAccess\Application\Handler\AssignRoleHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class AssignRoleController extends Controller
{
    public function __invoke(AssignRoleRequest $request, AssignRoleHandler $handler, TenantContext $tenantContext, string $organization, string $membership): JsonResponse
    {
        $assignmentId = $handler->handle(new AssignRoleCommand(
            MembershipId::fromString($membership),
            RoleId::fromString($request->validated('role_id')),
        ));

        return response()->json(['data' => ['role_assignment_id' => $assignmentId->value()], 'meta' => ['correlation_id' => $tenantContext->correlationId()->value()]], 201)
            ->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
