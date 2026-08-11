<?php

declare(strict_types=1);

namespace Tests\Application\Event;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sentinel\Event\Application\Command\IngestEventBatchCommand;
use Sentinel\Event\Application\Command\IngestEventBatchItem;
use Sentinel\Event\Domain\Exception\InvalidEvent;

final class IngestEventBatchCommandTest extends TestCase
{
    #[Test]
    public function it_normalizes_ordered_items_and_has_a_deterministic_batch_hash(): void
    {
        $batch = IngestEventBatchCommand::fromItems([$this->item('first', ['z' => 1, 'a' => 2]), $this->item('second')], 'batch-key');
        $equivalent = IngestEventBatchCommand::fromItems([$this->item('first', ['a' => 2, 'z' => 1]), $this->item('second')], 'batch-key');

        self::assertCount(2, $batch->events());
        self::assertSame('first', $batch->events()[0]->externalEventId());
        self::assertSame($batch->normalizedHash(), $equivalent->normalizedHash());
    }

    #[Test]
    public function it_enforces_batch_bounds_and_canonical_item_validation(): void
    {
        $this->expectException(InvalidEvent::class);
        IngestEventBatchCommand::fromItems([], 'batch-key');
    }

    #[Test]
    public function it_rejects_conflicting_duplicate_external_ids(): void
    {
        $this->expectException(InvalidEvent::class);
        IngestEventBatchCommand::fromItems([$this->item('same'), $this->item('same', ['message' => 'different'])], 'batch-key');
    }

    private function item(?string $externalId = null, array $payload = ['message' => 'failed']): IngestEventBatchItem
    {
        return new IngestEventBatchItem('application.error', '1', '2026-08-11T12:00:00Z', $payload, $externalId);
    }
}
