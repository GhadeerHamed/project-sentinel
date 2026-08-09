<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateRolePermissionsRequest;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\UpdateRolePermissionsCommand;
use Sentinel\IdentityAccess\Application\Handler\UpdateRolePermissionsHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\PermissionCode;
use Sentinel\IdentityAccess\Domain\ValueObject\RoleId;

final class UpdateRolePermissionsController extends Controller
{
    public function __invoke(UpdateRolePermissionsRequest $request, UpdateRolePermissionsHandler $handler, string $organization, string $role): Response
    {
        /** @var list<string> $grants */
        $grants = $request->validated('grant');
        /** @var list<string> $revocations */
        $revocations = $request->validated('revoke');
        $handler->handle(new UpdateRolePermissionsCommand(
            roleId: RoleId::fromString($role),
            grants: array_map(static fn (string $permission): PermissionCode => PermissionCode::fromString($permission), $grants),
            revocations: array_map(static fn (string $permission): PermissionCode => PermissionCode::fromString($permission), $revocations),
        ));

        return response()->noContent();
    }
}
