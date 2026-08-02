<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Event;

use Sentinel\Shared\Domain\Concurrency\ResourceVersion;

interface AggregateDomainEvent extends DomainEvent
{
    public function aggregateType(): string;

    public function aggregateId(): string;

    public function aggregateVersion(): ResourceVersion;
}
