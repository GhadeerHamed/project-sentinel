<?php

declare(strict_types=1);

namespace Sentinel\Event\Domain\ValueObject;

use Sentinel\Event\Domain\Exception\InvalidEvent;

enum EventType: string
{
    case APPLICATION_ERROR = 'application.error';
    case APPLICATION_HEALTH_FAILED = 'application.health_failed';
    case APPLICATION_HEALTH_RECOVERED = 'application.health_recovered';
    case DEPLOYMENT_COMPLETED = 'deployment.completed';
    case CUSTOM_OPERATIONAL_EVENT = 'custom.operational_event';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? throw InvalidEvent::forField('event_type');
    }

    public function category(): EventCategory
    {
        return match ($this) {
            self::APPLICATION_ERROR => EventCategory::ERROR,
            self::APPLICATION_HEALTH_FAILED, self::APPLICATION_HEALTH_RECOVERED => EventCategory::AVAILABILITY,
            self::DEPLOYMENT_COMPLETED => EventCategory::DEPLOYMENT,
            self::CUSTOM_OPERATIONAL_EVENT => EventCategory::PLATFORM,
        };
    }
}
