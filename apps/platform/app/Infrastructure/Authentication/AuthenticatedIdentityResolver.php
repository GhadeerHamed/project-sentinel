<?php

declare(strict_types=1);

namespace App\Infrastructure\Authentication;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final readonly class AuthenticatedIdentityResolver
{
    public function __construct(
        private AuthFactory $auth,
    ) {}

    public function currentIdentity(): ?IdentityReference
    {
        $user = $this->auth->guard('web')->user();

        if (! $user instanceof IdentityUserAuthenticatable) {
            return null;
        }

        return $user->identityReference();
    }
}
