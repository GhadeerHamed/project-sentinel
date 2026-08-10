<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use Sentinel\Event\Domain\Exception\InvalidEventSource;

enum EventSourceType: string
{
    case API_CLIENT = 'api_client';

    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower(trim($value))) ?? throw InvalidEventSource::forField('source_type');
    }
}
