<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Sentinel\Event\Application\Authentication\EventSourceIdentity;
use Sentinel\Event\Application\Command\IngestEventBatchCommand;
use Sentinel\Event\Application\Command\IngestEventBatchItem;
use Sentinel\Event\Application\Handler\IngestEventBatchHandler;
use Sentinel\Event\Domain\Repository\EventSourceRepository;
use Sentinel\Event\Domain\ValueObject\EventSourceId;
use Sentinel\Shared\Domain\Error\ApplicationOperationFailure;
use Sentinel\Shared\Domain\Organization\OrganizationId;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$instruction = json_decode((string) getenv('SENTINEL_CONCURRENCY_INSTRUCTION'), true, 512, JSON_THROW_ON_ERROR);
fwrite(STDOUT, "ready\n");
if (trim((string) fgets(STDIN)) !== 'go') {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'barrier_not_released'], JSON_THROW_ON_ERROR)."\n");
    exit(1);
}
try {
    if (($instruction['operation'] ?? null) === 'ingest_event_batch') {
        $source = $app->make(EventSourceRepository::class)->getById(EventSourceId::fromString((string) $instruction['event_source_id']), OrganizationId::fromString((string) $instruction['organization_id']));
        $items = array_map(static fn (array $item): IngestEventBatchItem => new IngestEventBatchItem($item['event_type'], $item['schema_version'], $item['occurred_at'], $item['payload'], $item['external_event_id'] ?? null, $item['severity'] ?? null, $item['attributes'] ?? [], $item['correlation_id'] ?? null, $item['causation_id'] ?? null, $item['trace_id'] ?? null), $instruction['events']);
        $result = $app->make(IngestEventBatchHandler::class)->handle(IngestEventBatchCommand::fromItems($items, (string) $instruction['idempotency_key']), EventSourceIdentity::fromEventSource($source));
        fwrite(STDOUT, json_encode(['ok' => true, 'event_ids' => array_map(static fn ($event): string => $event->id()->value(), $result->events())], JSON_THROW_ON_ERROR)."\n");
        DB::disconnect();
        exit(0);
    }
    $exists = DB::table((string) $instruction['table'])->where('id', (string) $instruction['id'])->exists();
    fwrite(STDOUT, json_encode(['ok' => true, 'exists' => $exists], JSON_THROW_ON_ERROR)."\n");
    DB::disconnect();
} catch (Throwable $exception) {
    DB::disconnect();
    $error = $exception instanceof ApplicationOperationFailure ? $exception->category()->value : 'worker_failed';
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => $error], JSON_THROW_ON_ERROR)."\n");
    exit(1);
}
