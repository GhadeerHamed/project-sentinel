<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuspendMembershipRequest;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\RemoveMembershipCommand;
use Sentinel\IdentityAccess\Application\Handler\RemoveMembershipHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class RemoveMembershipController extends Controller
{
    public function __invoke(SuspendMembershipRequest $request, RemoveMembershipHandler $handler, string $organization, string $membership): Response
    {
        $version = $handler->handle(new RemoveMembershipCommand(MembershipId::fromString($membership), ExpectedVersion::fromInt($request->expectedVersion())));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
