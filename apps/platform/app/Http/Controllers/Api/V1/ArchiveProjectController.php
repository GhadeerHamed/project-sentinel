<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArchiveProjectRequest;
use Illuminate\Http\Response;
use Sentinel\Project\Application\Command\ArchiveProjectCommand;
use Sentinel\Project\Application\Handler\ArchiveProjectHandler;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class ArchiveProjectController extends Controller
{
    public function __invoke(ArchiveProjectRequest $request, ArchiveProjectHandler $handler): Response
    {
        $version = $handler->handle(new ArchiveProjectCommand(
            projectId: $request->projectId(),
            expectedVersion: ExpectedVersion::fromInt($request->expectedVersion()),
        ));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
