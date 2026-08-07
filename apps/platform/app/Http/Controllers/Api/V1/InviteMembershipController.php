<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InviteMembershipRequest;
use Illuminate\Http\JsonResponse;
use Sentinel\IdentityAccess\Application\Command\InviteMembershipCommand;
use Sentinel\IdentityAccess\Application\Handler\InviteMembershipHandler;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class InviteMembershipController extends Controller
{
    public function __invoke(InviteMembershipRequest $request, InviteMembershipHandler $handler, TenantContext $tenantContext): JsonResponse
    {
        $result = $handler->handle(new InviteMembershipCommand(IdentityId::fromString($request->validated('user_identity_id'))));

        return response()->json(['data' => ['membership_id' => $result->membershipId()->value(), 'status' => $result->status()->value], 'meta' => ['correlation_id' => $tenantContext->correlationId()->value()]], 201)
            ->header('X-Correlation-ID', $tenantContext->correlationId()->value());
    }
}
