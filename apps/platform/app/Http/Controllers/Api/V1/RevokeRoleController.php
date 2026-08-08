<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RevokeRoleRequest;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\RevokeRoleCommand;
use Sentinel\IdentityAccess\Application\Handler\RevokeRoleHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleAssignmentId;

final class RevokeRoleController extends Controller
{
    public function __invoke(RevokeRoleRequest $request, RevokeRoleHandler $handler, string $organization, string $roleAssignment): Response
    {
        $handler->handle(new RevokeRoleCommand(RoleAssignmentId::fromString($roleAssignment)));

        return response()->noContent();
    }
}
