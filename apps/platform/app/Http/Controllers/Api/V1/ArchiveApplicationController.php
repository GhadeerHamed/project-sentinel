<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ArchiveApplicationRequest;
use Illuminate\Http\Response;
use Sentinel\Application\Application\Command\ArchiveApplicationCommand;
use Sentinel\Application\Application\Handler\ArchiveApplicationHandler;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class ArchiveApplicationController extends Controller
{
    public function __invoke(ArchiveApplicationRequest $request, ArchiveApplicationHandler $handler): Response
    {
        $version = $handler->handle(new ArchiveApplicationCommand($request->projectId(), $request->applicationId(), ExpectedVersion::fromInt($request->expectedVersion())));

        return response()->noContent()->header('ETag', sprintf('"%d"', $version->value()));
    }
}
