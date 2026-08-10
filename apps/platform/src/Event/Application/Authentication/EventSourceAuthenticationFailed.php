<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Authentication;

use RuntimeException;

final class EventSourceAuthenticationFailed extends RuntimeException
{
    public static function invalidCredential(): self
    {
        return new self('The Event Source credential is invalid.');
    }
}
