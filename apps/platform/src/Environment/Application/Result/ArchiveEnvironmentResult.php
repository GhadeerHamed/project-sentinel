<?php

declare(strict_types=1);

namespace Sentinel\Environment\Application\Result;

use Sentinel\Shared\Domain\Concurrency\ResourceVersion;

final readonly class ArchiveEnvironmentResult
{
    public function __construct(private ResourceVersion $version) {}

    public function version(): ResourceVersion
    {
        return $this->version;
    }
}
