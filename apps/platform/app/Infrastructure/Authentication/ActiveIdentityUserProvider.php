<?php

declare(strict_types=1);

namespace App\Infrastructure\Authentication;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

final class ActiveIdentityUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        if (! $user instanceof IdentityUserAuthenticatable || ! $user->canAuthenticate()) {
            return false;
        }

        return parent::validateCredentials($user, $credentials);
    }
}
