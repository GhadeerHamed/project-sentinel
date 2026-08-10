<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArchiveEnvironmentRequest;
use Illuminate\Http\Response;
use Sentinel\Environment\Application\Command\ArchiveEnvironmentCommand;
use Sentinel\Environment\Application\Handler\ArchiveEnvironmentHandler;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class ArchiveEnvironmentController extends Controller
{
    public function __invoke(ArchiveEnvironmentRequest $request, ArchiveEnvironmentHandler $handler): Response
    {
        $result = $handler->handle(new ArchiveEnvironmentCommand(
            $request->projectId(),
            $request->applicationId(),
            $request->environmentId(),
            ExpectedVersion::fromInt($request->expectedVersion()),
        ));

        return response()->noContent()->header('ETag', sprintf('"%d"', $result->version()->value()));
    }
}
