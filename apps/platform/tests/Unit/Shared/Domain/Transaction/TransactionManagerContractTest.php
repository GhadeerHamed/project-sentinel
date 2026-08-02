<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Transaction;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Throwable;

final class TransactionManagerContractTest extends TestCase
{
    #[Test]
    public function it_defines_begin_commit_rollback_and_transactional_boundaries(): void
    {
        $contract = new ReflectionClass(TransactionManager::class);

        self::assertTrue($contract->isInterface());

        $this->assertMethodReturnType($contract, 'begin', 'void');
        $this->assertMethodReturnType($contract, 'commit', 'void');
        $this->assertMethodReturnType($contract, 'rollBack', 'void');
        $this->assertMethodReturnType($contract, 'transactional', 'mixed');
    }

    #[Test]
    public function transactional_commits_when_operation_succeeds(): void
    {
        $manager = new InMemoryTransactionManager;

        $result = $manager->transactional(static fn (): string => 'ok');

        self::assertSame('ok', $result);
        self::assertSame(1, $manager->beginCount);
        self::assertSame(1, $manager->commitCount);
        self::assertSame(0, $manager->rollBackCount);
    }

    #[Test]
    public function transactional_rolls_back_when_operation_fails(): void
    {
        $manager = new InMemoryTransactionManager;

        try {
            $manager->transactional(static function (): never {
                throw new RuntimeException('boom');
            });

            self::fail('Expected exception was not rethrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(1, $manager->beginCount);
        self::assertSame(0, $manager->commitCount);
        self::assertSame(1, $manager->rollBackCount);
    }

    private function assertMethodReturnType(
        ReflectionClass $contract,
        string $methodName,
        string $expectedType,
    ): void {
        $method = $contract->getMethod($methodName);
        $returnType = $method->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $returnType);
        self::assertSame($expectedType, $returnType->getName());
    }
}

final class InMemoryTransactionManager implements TransactionManager
{
    public int $beginCount = 0;

    public int $commitCount = 0;

    public int $rollBackCount = 0;

    public function begin(): void
    {
        $this->beginCount++;
    }

    public function commit(): void
    {
        $this->commitCount++;
    }

    public function rollBack(): void
    {
        $this->rollBackCount++;
    }

    public function transactional(callable $operation): mixed
    {
        $this->begin();

        try {
            $result = $operation();
            $this->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollBack();

            throw $exception;
        }
    }
}
