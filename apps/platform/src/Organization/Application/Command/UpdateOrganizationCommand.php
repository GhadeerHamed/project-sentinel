<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Command;

use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final readonly class UpdateOrganizationCommand
{
    public function __construct(
        private ?string $name,
        private ?string $timezone,
        private ExpectedVersion $expectedVersion,
    ) {}

    public function name(): ?string
    {
        return $this->name;
    }

    public function timezone(): ?string
    {
        return $this->timezone;
    }

    public function expectedVersion(): ExpectedVersion
    {
        return $this->expectedVersion;
    }
}
