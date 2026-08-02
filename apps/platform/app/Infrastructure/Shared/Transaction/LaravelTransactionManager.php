<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Transaction;

use Illuminate\Support\Facades\DB;
use Sentinel\Shared\Domain\Transaction\TransactionManager;
use Throwable;

final class LaravelTransactionManager implements TransactionManager
{
    public function begin(): void
    {
        DB::beginTransaction();
    }

    public function commit(): void
    {
        DB::commit();
    }

    public function rollBack(): void
    {
        DB::rollBack();
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
