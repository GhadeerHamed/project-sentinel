<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Authentication;

use DateTimeImmutable;
use Sentinel\Event\Application\Result\IssuedEventSourceCredential;
use Sentinel\Event\Domain\Entity\EventSourceCredential;

final class EventSourceCredentialIssuer
{
    public function issue(
        string $credentialId,
        string $organizationId,
        string $eventSourceId,
        DateTimeImmutable $at,
    ): IssuedEventSourceCredential {
        $secret = bin2hex(random_bytes(32));
        $secretHash = password_hash($secret, PASSWORD_DEFAULT);

        return new IssuedEventSourceCredential(
            credential: EventSourceCredential::issue(
                id: $credentialId,
                organizationId: $organizationId,
                eventSourceId: $eventSourceId,
                secretHash: $secretHash,
                at: $at,
            ),
            bearerCredential: sprintf('esc_%s.%s', $credentialId, $secret),
        );
    }
}
