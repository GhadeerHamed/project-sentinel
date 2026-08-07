<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuspendMembershipRequest;
use App\Infrastructure\Authentication\AuthenticatedIdentityResolver;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\AcceptMembershipInvitationCommand;
use Sentinel\IdentityAccess\Application\Handler\AcceptMembershipInvitationHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Sentinel\Shared\Domain\Identity\IdentityId;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class AcceptMembershipInvitationController extends Controller
{
    public function __invoke(SuspendMembershipRequest $request, AcceptMembershipInvitationHandler $handler, AuthenticatedIdentityResolver $identities, UuidV7Generator $uuidGenerator, string $organization, string $membership): Response
    {
        $identity = $identities->currentIdentity();
        abort_if($identity === null, 401);
        $version = $handler->handle(new AcceptMembershipInvitationCommand(OrganizationId::fromString($organization), MembershipId::fromString($membership), IdentityId::fromString($identity->identityId()), ExpectedVersion::fromInt($request->expectedVersion()), $uuidGenerator->generate()));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
