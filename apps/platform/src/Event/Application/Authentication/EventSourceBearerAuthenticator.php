<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Authentication;

use Sentinel\Event\Domain\Exception\EventSourceNotFound;
use Sentinel\Event\Domain\Exception\InvalidEventSourceCredential;
use Sentinel\Event\Domain\Repository\EventSourceCredentialRepository;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Shared\Domain\Organization\OrganizationId;
use Sentinel\Shared\Domain\Time\Clock;

final readonly class EventSourceBearerAuthenticator
{
    public function __construct(
        private EventSourceCredentialRepository $credentials,
        private EventSourceRepository $eventSources,
        private Clock $clock,
    ) {}

    public function authenticate(string $authorizationHeader, OrganizationId $organizationId): EventSourceIdentity
    {
        try {
            $presentedCredential = EventSourceCredentialPresentation::fromAuthorizationHeader($authorizationHeader);
            $credential = $this->credentials->findById($presentedCredential->credentialId(), $organizationId);

            if ($credential === null
                || ! $credential->isActiveAt($this->clock->now())
                || ! $credential->matchesSecret($presentedCredential->secret())) {
                throw EventSourceAuthenticationFailed::invalidCredential();
            }

            $eventSource = $this->eventSources->getById($credential->eventSourceId(), $organizationId);

            if (! $eventSource->isActive()) {
                throw EventSourceAuthenticationFailed::invalidCredential();
            }

            return EventSourceIdentity::fromEventSource($eventSource);
        } catch (InvalidEventSourceCredential|EventSourceNotFound) {
            throw EventSourceAuthenticationFailed::invalidCredential();
        }
    }
}
