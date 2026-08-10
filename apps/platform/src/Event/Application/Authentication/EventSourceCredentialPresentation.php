<?php

declare(strict_types=1);

namespace Sentinel\Event\Application\Authentication;

use Sentinel\Event\Domain\Exception\InvalidEventSourceCredential;
use Sentinel\Event\Domain\ValueObject\EventSourceCredentialId;

final readonly class EventSourceCredentialPresentation
{
    private function __construct(
        private EventSourceCredentialId $credentialId,
        private string $secret,
    ) {}

    public static function fromAuthorizationHeader(string $authorizationHeader): self
    {
        if (preg_match('/\ABearer[ ]+(?<credential>esc_(?<id>[0-9a-f-]+)\.(?<secret>[A-Za-z0-9_-]+))\z/i', trim($authorizationHeader), $matches) !== 1) {
            throw InvalidEventSourceCredential::forField('authorization');
        }

        return new self(
            credentialId: EventSourceCredentialId::fromString($matches['id']),
            secret: $matches['secret'],
        );
    }

    public function credentialId(): EventSourceCredentialId
    {
        return $this->credentialId;
    }

    public function secret(): string
    {
        return $this->secret;
    }
}
