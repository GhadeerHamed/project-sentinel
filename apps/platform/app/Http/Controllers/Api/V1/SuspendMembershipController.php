<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuspendMembershipRequest;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\SuspendMembershipCommand;
use Sentinel\IdentityAccess\Application\Handler\SuspendMembershipHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class SuspendMembershipController extends Controller
{
    public function __invoke(SuspendMembershipRequest $request, SuspendMembershipHandler $handler, string $organization, string $membership): Response
    {
        $handler->handle(new SuspendMembershipCommand(MembershipId::fromString($membership), ExpectedVersion::fromInt($request->expectedVersion())));

        return response()->noContent();
    }
}
