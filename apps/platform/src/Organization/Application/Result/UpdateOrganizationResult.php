<?php

declare(strict_types=1);

namespace Sentinel\Organization\Application\Result;

use Sentinel\Shared\Domain\Concurrency\ResourceVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final readonly class UpdateOrganizationResult
{
    public function __construct(private OrganizationId $organizationId, private string $name, private string $timezone, private ResourceVersion $version) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function version(): ResourceVersion
    {
        return $this->version;
    }
}
