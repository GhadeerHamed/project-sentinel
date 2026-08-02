<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Tenant;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identifier\InvalidResourceId;
use Sentinel\Shared\Domain\Identity\InvalidIdentityType;
use Sentinel\Shared\Domain\Tenant\TenantContext;

final class TenantContextTest extends TestCase
{
    private const ORGANIZATION_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c07398f';

    private const IDENTITY_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073991';

    private const CORRELATION_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073992';

    private const CAUSATION_ID = '01890f7a-5b7c-7cc0-98c4-dc0c0c073993';

    #[Test]
    public function it_creates_an_immutable_tenant_context_from_valid_values(): void
    {
        $context = TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::IDENTITY_ID,
            identityType: 'user',
            correlationId: self::CORRELATION_ID,
            causationId: self::CAUSATION_ID,
        );

        self::assertSame(self::ORGANIZATION_ID, $context->organizationId()->value());
        self::assertSame(self::IDENTITY_ID, $context->actingIdentity()->identityId());
        self::assertSame('user', $context->actingIdentity()->identityType());
        self::assertSame(self::CORRELATION_ID, $context->correlationId()->value());
        self::assertSame(self::CAUSATION_ID, $context->causationId()?->value());
    }

    #[Test]
    public function equality_requires_all_tenant_context_values_to_match(): void
    {
        $first = TenantContext::fromValues(
            self::ORGANIZATION_ID,
            self::IDENTITY_ID,
            'service',
            self::CORRELATION_ID,
            self::CAUSATION_ID,
        );
        $same = TenantContext::fromValues(
            self::ORGANIZATION_ID,
            self::IDENTITY_ID,
            'service',
            self::CORRELATION_ID,
            self::CAUSATION_ID,
        );
        $different = TenantContext::fromValues(
            self::ORGANIZATION_ID,
            self::IDENTITY_ID,
            'agent',
            self::CORRELATION_ID,
            self::CAUSATION_ID,
        );

        self::assertTrue($first->equals($same));
        self::assertFalse($first->equals($different));
    }

    #[Test]
    public function it_rejects_invalid_identifier_values_during_construction(): void
    {
        $this->expectException(InvalidResourceId::class);

        TenantContext::fromValues(
            organizationId: '550e8400-e29b-41d4-a716-446655440000',
            identityId: self::IDENTITY_ID,
            identityType: 'user',
            correlationId: self::CORRELATION_ID,
        );
    }

    #[Test]
    public function it_rejects_invalid_identity_types_during_construction(): void
    {
        $this->expectException(InvalidIdentityType::class);

        TenantContext::fromValues(
            organizationId: self::ORGANIZATION_ID,
            identityId: self::IDENTITY_ID,
            identityType: 'daemon',
            correlationId: self::CORRELATION_ID,
        );
    }
}
