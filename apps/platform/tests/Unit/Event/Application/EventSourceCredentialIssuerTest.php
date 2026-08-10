<?php

declare(strict_types=1);

namespace Tests\Unit\Event\Application;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Event\Application\Authentication\EventSourceCredentialIssuer;
use Sentinel\Event\Application\Authentication\EventSourceCredentialPresentation;

final class EventSourceCredentialIssuerTest extends TestCase
{
    #[Test]
    public function it_issues_an_opaque_bearer_credential_and_persists_only_its_hash(): void
    {
        $issued = (new EventSourceCredentialIssuer)->issue(
            credentialId: '0198f3b6-48ae-7a45-9192-6f8029df5f10',
            organizationId: '0198f3b6-48ae-7a45-9192-6f8029df5f11',
            eventSourceId: '0198f3b6-48ae-7a45-9192-6f8029df5f12',
            at: new DateTimeImmutable('2026-08-10T12:00:00+00:00'),
        );

        $presentation = EventSourceCredentialPresentation::fromAuthorizationHeader('Bearer '.$issued->bearerCredential());

        self::assertSame('0198f3b6-48ae-7a45-9192-6f8029df5f10', $presentation->credentialId()->value());
        self::assertTrue($issued->credential()->matchesSecret($presentation->secret()));
        self::assertNotSame($presentation->secret(), $issued->credential()->secretHash());
        self::assertStringStartsWith('esc_0198f3b6-48ae-7a45-9192-6f8029df5f10.', $issued->bearerCredential());
    }
}
