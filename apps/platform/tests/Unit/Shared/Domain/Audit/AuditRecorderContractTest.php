<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Audit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use Sentinel\Shared\Domain\Audit\AuditRecord;
use Sentinel\Shared\Domain\Audit\AuditRecorder;

final class AuditRecorderContractTest extends TestCase
{
    #[Test]
    public function it_defines_a_framework_independent_audit_recording_contract(): void
    {
        $contract = new ReflectionClass(AuditRecorder::class);

        self::assertTrue($contract->isInterface());

        $method = $contract->getMethod('record');
        $returnType = $method->getReturnType();
        $parameterType = $method->getParameters()[0]->getType();

        self::assertInstanceOf(ReflectionNamedType::class, $returnType);
        self::assertInstanceOf(ReflectionNamedType::class, $parameterType);
        self::assertSame('void', $returnType->getName());
        self::assertSame(AuditRecord::class, $parameterType->getName());
    }
}
