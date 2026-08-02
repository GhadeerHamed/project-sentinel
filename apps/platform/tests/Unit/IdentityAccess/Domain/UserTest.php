<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Domain\Entity\User;
use Sentinel\IdentityAccess\Domain\Exception\InvalidIdentityAccessState;

final class UserTest extends TestCase
{
    #[Test]
    public function it_creates_an_immutable_user_with_identity_status(): void
    {
        $createdAt = new DateTimeImmutable('2026-08-02T14:00:00+00:00');
        $updatedAt = new DateTimeImmutable('2026-08-02T14:00:01+00:00');

        $user = User::fromValues(
            identityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d01',
            email: 'User@Example.com',
            displayName: 'Sentinel User',
            status: 'active',
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        self::assertSame('01890f7a-5b7c-7cc0-98c4-dc0c0c073d01', $user->identityId()->value());
        self::assertSame('user@example.com', $user->email());
        self::assertSame('Sentinel User', $user->displayName());
        self::assertSame('active', $user->status()->value);
        self::assertSame($createdAt, $user->createdAt());
        self::assertSame($updatedAt, $user->updatedAt());
        self::assertTrue($user->canAuthenticate());
        self::assertSame(1, $user->version()->value());
    }

    #[Test]
    public function it_preserves_hydrated_user_resource_version_and_does_not_change_it_on_reads(): void
    {
        $user = User::fromValues(
            identityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d11',
            email: 'user@example.com',
            displayName: 'Sentinel User',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-02T14:10:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:10:00+00:00'),
            version: 5,
        );

        self::assertSame(5, $user->version()->value());

        self::assertTrue($user->canAuthenticate());

        self::assertSame(5, $user->version()->value());
    }

    #[Test]
    public function it_rejects_invalid_user_email_addresses(): void
    {
        $this->expectException(InvalidIdentityAccessState::class);

        User::fromValues(
            identityId: '01890f7a-5b7c-7cc0-98c4-dc0c0c073d01',
            email: 'invalid',
            displayName: 'Sentinel User',
            status: 'active',
            createdAt: new DateTimeImmutable('2026-08-02T14:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-08-02T14:00:01+00:00'),
        );
    }
}
