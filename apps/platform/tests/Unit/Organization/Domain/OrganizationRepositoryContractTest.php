<?php

declare(strict_types=1);

namespace Tests\Unit\Organization\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sentinel\Organization\Domain\Repository\OrganizationRepository;
use Sentinel\Shared\Domain\Concurrency\ExpectedVersion;

final class OrganizationRepositoryContractTest extends TestCase
{
    #[Test]
    public function it_defines_organization_repository_contract(): void
    {
        $repository = new ReflectionClass(OrganizationRepository::class);

        self::assertTrue($repository->isInterface());
        self::assertTrue($repository->hasMethod('getById'));
        self::assertTrue($repository->hasMethod('save'));

        $saveParameters = $repository->getMethod('save')->getParameters();
        self::assertSame(ExpectedVersion::class, $saveParameters[1]->getType()?->getName());

        self::assertStringContainsString(
            '@throws OrganizationNotFound',
            (string) $repository->getMethod('getById')->getDocComment(),
        );
    }
}
