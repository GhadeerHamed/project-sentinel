<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Outbox;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Sentinel\Shared\Domain\Outbox\OutboxMessageStore;

final class OutboxMessageStoreContractTest extends TestCase
{
    #[Test]
    public function it_defines_a_framework_independent_outbox_append_contract(): void
    {
        $contract = new ReflectionClass(OutboxMessageStore::class);

        self::assertTrue($contract->isInterface());
        self::assertTrue($contract->hasMethod('append'));
    }
}
