<?php

declare(strict_types=1);

namespace Tests\Application\IdentityAccess;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\IdentityAccess\Application\Authorization\AdministrativeAccessInvariant;
use Sentinel\IdentityAccess\Domain\Exception\AdministrativeAccessInvariantViolation;
use Sentinel\IdentityAccess\Domain\Repository\AdministrativeAccessRepository;
use Sentinel\IdentityAccess\Domain\ValueObject\CorePermissions;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class AdministrativeAccessInvariantTest extends TestCase
{
    #[Test]
    public function it_raises_a_typed_invariant_violation_when_no_administrative_identity_remains(): void
    {
        $organizationId = OrganizationId::fromString('01890f7a-5b7c-7cc0-98c4-dc0c0c077101');
        $at = new DateTimeImmutable('2026-08-09T12:00:00+00:00');
        $repository = $this->createMock(AdministrativeAccessRepository::class);
        $repository->expects(self::once())
            ->method('hasActiveHumanIdentityWithEffectivePermission')
            ->with($organizationId, CorePermissions::manageOrganizationMembers(), $at)
            ->willReturn(false);

        $this->expectException(AdministrativeAccessInvariantViolation::class);

        (new AdministrativeAccessInvariant($repository))->assertRetained($organizationId, $at);
    }
}
