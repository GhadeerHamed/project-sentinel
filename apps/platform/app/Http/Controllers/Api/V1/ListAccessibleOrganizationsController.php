<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Infrastructure\Authentication\AuthenticatedIdentityResolver;
use Illuminate\Http\JsonResponse;
use LogicException;
use Sentinel\Organization\Application\Handler\ListAccessibleOrganizationsHandler;

final class ListAccessibleOrganizationsController extends Controller
{
    public function __invoke(AuthenticatedIdentityResolver $identities, ListAccessibleOrganizationsHandler $handler): JsonResponse
    {
        $identity = $identities->currentIdentity() ?? throw new LogicException('The authenticated identity could not be resolved.');
        $organizations = $handler->handle($identity);

        return OrganizationResource::collection($organizations)->response();
    }
}
