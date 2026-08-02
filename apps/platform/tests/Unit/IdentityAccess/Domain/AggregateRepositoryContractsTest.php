<?php

declare(strict_types=1);

namespace Tests\Unit\IdentityAccess\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Sentinel\IdentityAccess\Domain\Repository\MembershipRepository;
use Sentinel\IdentityAccess\Domain\Repository\UserRepository;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;
use Sentinel\Shared\Domain\Organization\OrganizationId;

final class AggregateRepositoryContractsTest extends TestCase
{
    #[Test]
    public function it_defines_user_and_membership_repository_contracts_with_explicit_tenant_scope(): void
    {
        $userRepository = new ReflectionClass(UserRepository::class);
        $membershipRepository = new ReflectionClass(MembershipRepository::class);

        self::assertTrue($userRepository->isInterface());
        self::assertTrue($membershipRepository->isInterface());

        $getByIdentity = $membershipRepository->getMethod('getByIdentity');
        $parameters = $getByIdentity->getParameters();

        self::assertInstanceOf(ReflectionNamedType::class, $parameters[0]->getType());
        self::assertSame(OrganizationId::class, $parameters[0]->getType()->getName());

        $membershipSaveParameters = $membershipRepository->getMethod('save')->getParameters();
        self::assertSame(ExpectedVersion::class, $membershipSaveParameters[1]->getType()?->getName());

        $userSaveParameters = $userRepository->getMethod('save')->getParameters();
        self::assertSame(ExpectedVersion::class, $userSaveParameters[1]->getType()?->getName());

        self::assertStringContainsString(
            '@throws UserNotFound',
            (string) $userRepository->getMethod('getByIdentityId')->getDocComment(),
        );
        self::assertStringContainsString(
            '@throws MembershipNotFound',
            (string) $membershipRepository->getMethod('getById')->getDocComment(),
        );
    }
}
