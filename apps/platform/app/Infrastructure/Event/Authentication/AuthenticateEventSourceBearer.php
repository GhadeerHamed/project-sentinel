<?php

declare(strict_types=1);

namespace App\Infrastructure\Event\Authentication;

use Closure;
use Illuminate\Http\Request;
use Sentinel\Event\Application\Authentication\EventSourceAuthenticationFailed;
use Sentinel\Event\Application\Authentication\EventSourceBearerAuthenticator;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateEventSourceBearer
{
    public function __construct(
        private EventSourceBearerAuthenticator $authenticator,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeOrganizationId = $request->route('organization');

        if (! is_string($routeOrganizationId)) {
            throw new EventSourceAuthenticationException;
        }

        try {
            $eventSourceIdentity = $this->authenticator->authenticate(
                authorizationHeader: $request->header('Authorization', ''),
                organizationId: OrganizationId::fromString($routeOrganizationId),
            );
        } catch (EventSourceAuthenticationFailed|InvalidResourceId) {
            throw new EventSourceAuthenticationException;
        }

        $request->attributes->set(EventSourceIdentity::REQUEST_ATTRIBUTE, $eventSourceIdentity);

        return $next($request);
    }
}
