<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Command;

use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class SuspendOrganizationCommand
{
    public function __construct(private ExpectedVersion $expectedVersion) {}

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
