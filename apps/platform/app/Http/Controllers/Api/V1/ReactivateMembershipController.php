<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuspendMembershipRequest;
use Illuminate\Http\Response;
use Sentinel\IdentityAccess\Application\Command\ReactivateMembershipCommand;
use Sentinel\IdentityAccess\Application\Handler\ReactivateMembershipHandler;
use Sentinel\IdentityAccess\Domain\ValueObject\MembershipId;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class ReactivateMembershipController extends Controller
{
    public function __invoke(SuspendMembershipRequest $request, ReactivateMembershipHandler $handler, string $organization, string $membership): Response
    {
        $version = $handler->handle(new ReactivateMembershipCommand(MembershipId::fromString($membership), ExpectedVersion::fromInt($request->expectedVersion())));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
