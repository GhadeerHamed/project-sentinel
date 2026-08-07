<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOrganizationRequest;
use App\Infrastructure\Authentication\AuthenticatedIdentityResolver;
use Illuminate\Http\JsonResponse;
use LogicException;
use Sentinel\Organization\Application\Command\CreateOrganizationCommand;
use Sentinel\Organization\Application\Handler\CreateOrganizationHandler;
use Sentinel\Shared\Domain\Identifier\CorrelationId;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;

final class CreateOrganizationController extends Controller
{
    public function __invoke(
        CreateOrganizationRequest $request,
        AuthenticatedIdentityResolver $identityResolver,
        CreateOrganizationHandler $handler,
        UuidV7Generator $uuidV7Generator,
    ): JsonResponse {
        $identity = $identityResolver->currentIdentity();

        if ($identity === null) {
            throw new LogicException('The authenticated identity could not be resolved.');
        }

        $correlationId = $this->correlationId($request, $uuidV7Generator);
        $result = $handler->handle(CreateOrganizationCommand::fromValues(
            organizationName: $request->validated('name'),
            organizationSlug: $request->validated('slug'),
            creatorIdentityId: $identity->identityId(),
            creatorIdentityType: $identity->identityType(),
            correlationId: $correlationId,
        ));

        return response()->json([
            'data' => [
                'organization_id' => $result->organizationId()->value(),
                'membership_id' => $result->creatorMembershipId()->value(),
                'role_assignment_id' => $result->initialRoleAssignmentId()->value(),
            ],
            'meta' => ['correlation_id' => $correlationId],
        ], 201)->header('X-Correlation-ID', $correlationId);
    }

    private function correlationId(CreateOrganizationRequest $request, UuidV7Generator $uuidV7Generator): string
    {
        $correlationId = $request->attributes->get('correlation_id');

        return is_string($correlationId)
            ? CorrelationId::fromString($correlationId)->value()
            : $uuidV7Generator->generate();
    }
}
