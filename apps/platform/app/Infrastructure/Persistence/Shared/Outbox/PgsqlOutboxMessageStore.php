<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Shared\Outbox;

use App\Infrastructure\Persistence\Shared\Records\OutboxMessageRecord;
use Sentinel\Shared\Domain\Outbox\OutboxMessage;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;

final class PgsqlOutboxMessageStore implements OutboxMessageStore
{
    public function append(OutboxMessage $message): void
    {
        OutboxMessageRecord::query()->create($message->jsonSerialize());
    }
}
