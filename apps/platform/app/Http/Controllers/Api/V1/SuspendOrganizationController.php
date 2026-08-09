<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SuspendMembershipRequest;
use Illuminate\Http\Response;
use Sentinel\Organization\Application\Command\SuspendOrganizationCommand;
use Sentinel\Organization\Application\Handler\SuspendOrganizationHandler;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class SuspendOrganizationController extends Controller
{
    public function __invoke(SuspendMembershipRequest $request, SuspendOrganizationHandler $handler, string $organization): Response
    {
        $version = $handler->handle(new SuspendOrganizationCommand(ExpectedVersion::fromInt($request->expectedVersion())));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
