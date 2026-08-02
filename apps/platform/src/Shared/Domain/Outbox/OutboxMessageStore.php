<?php

declare(strict_types=1);

namespace Sentinel\Shared\Domain\Outbox;

interface OutboxMessageStore
{
    public function append(OutboxMessage $message): void;
}
