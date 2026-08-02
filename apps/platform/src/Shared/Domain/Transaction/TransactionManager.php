<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Transaction;

interface TransactionManager
{
    public function begin(): void;

    public function commit(): void;

    public function rollBack(): void;

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $operation
     * @return TResult
     */
    public function transactional(callable $operation): mixed;
}
