<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Authentication;

use Illuminate\Auth\AuthenticationException;

final class EventSourceAuthenticationException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('The Event Source credential is invalid.');
    }
}
