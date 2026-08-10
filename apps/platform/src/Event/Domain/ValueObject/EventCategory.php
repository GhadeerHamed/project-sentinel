<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

enum EventCategory: string
{
    case AVAILABILITY = 'availability';
    case DEPLOYMENT = 'deployment';
    case ERROR = 'error';
    case PLATFORM = 'platform';
}
