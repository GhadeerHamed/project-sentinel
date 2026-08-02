<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Organization\Policy;

use App\Infrastructure\Organization\Policy\ActiveHumanUserMayCreateOrganizationPolicy;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Shared\Domain\Identity\IdentityReference;

final class ActiveHumanUserMayCreateOrganizationPolicyTest extends TestCase
{
    private ActiveHumanUserMayCreateOrganizationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ActiveHumanUserMayCreateOrganizationPolicy;
    }

    #[Test]
    public function it_allows_human_user_identities(): void
    {
        $creator = IdentityReference::fromString(
            '01890f7a-5b7c-7cc0-98c4-dc0c0c075100',
            'user',
        );

        self::assertTrue($this->policy->allows($creator));
    }

    #[Test]
    public function it_rejects_non_human_identity_types(): void
    {
        foreach (['plugin', 'agent', 'service', 'api_client', 'platform'] as $identityType) {
            $creator = IdentityReference::fromString(
                '01890f7a-5b7c-7cc0-98c4-dc0c0c075101',
                $identityType,
            );

            self::assertFalse($this->policy->allows($creator));
        }
    }
}
