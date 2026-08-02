<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Identity;

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Identity\IdentityReference;
use Sentinel\Shared\Domain\Identity\InvalidIdentityType;

final class IdentityReferenceTest extends TestCase
{
    private const IDENTITY_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c07398f';

    #[Test]
    public function it_creates_an_immutable_identity_reference_from_valid_input(): void
    {
        $reference = IdentityReference::fromString(
            identityId: self::IDENTITY_ID,
            identityType: 'user',
        );

        self::assertSame(self::IDENTITY_ID, $reference->identityId());
        self::assertSame('user', $reference->identityType());
        self::assertSame('user:'.self::IDENTITY_ID, (string) $reference);
    }

    /**
     * @throws JsonException
     */
    #[Test]
    public function it_serializes_to_json_with_identity_id_and_type(): void
    {
        $reference = IdentityReference::fromString(
            identityId: self::IDENTITY_ID,
            identityType: 'api_client',
        );

        self::assertSame(
            '{"identity_id":"'.self::IDENTITY_ID.'","identity_type":"api_client"}',
            json_encode($reference, JSON_THROW_ON_ERROR),
        );
    }

    #[Test]
    public function it_compares_identity_reference_by_both_type_and_id(): void
    {
        $first = IdentityReference::fromString(self::IDENTITY_ID, 'service');
        $same = IdentityReference::fromString(self::IDENTITY_ID, 'service');
        $differentType = IdentityReference::fromString(self::IDENTITY_ID, 'agent');

        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($differentType));
    }

    #[Test]
    public function it_rejects_an_unsupported_identity_type(): void
    {
        $this->expectException(InvalidIdentityType::class);

        IdentityReference::fromString(self::IDENTITY_ID, 'daemon');
    }

    #[Test]
    public function it_rejects_a_non_uuid_v7_identity_id(): void
    {
        $this->expectException(InvalidResourceId::class);

        IdentityReference::fromString(
            identityId: '550e8400-e29b-41d4-a716-446655440000',
            identityType: 'user',
        );
    }
}
