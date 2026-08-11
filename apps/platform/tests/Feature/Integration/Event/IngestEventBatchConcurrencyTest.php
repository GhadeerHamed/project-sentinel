<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Event;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class IngestEventBatchConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function identical_batches_converge_on_one_ordered_idempotent_result(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL concurrency coverage requires DB_CONNECTION=pgsql.');
        }
        $fixture = $this->fixture();
        $instruction = ['operation' => 'ingest_event_batch', ...$fixture, 'idempotency_key' => 'same-batch', 'events' => [
            ['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:00Z', 'payload' => ['message' => 'one']],
            ['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:01Z', 'payload' => ['message' => 'two']],
        ]];
        DB::disconnect();
        $workers = [$this->worker($instruction), $this->worker($instruction)];
        foreach ($workers as [$worker]) {
            $worker->start();
        }
        foreach ($workers as [$worker]) {
            foreach ($worker as $output) {
                if (str_contains($output, 'ready')) {
                    break;
                }
            }
        }
        foreach ($workers as [, $input]) {
            $input->write("go\n");
            $input->close();
        }
        $results = [];
        foreach ($workers as [$worker]) {
            $worker->wait();
            self::assertTrue($worker->isSuccessful());
            $lines = array_values(array_filter(explode("\n", $worker->getOutput())));
            $results[] = json_decode((string) end($lines), true, 512, JSON_THROW_ON_ERROR);
        }
        DB::purge();
        self::assertSame(['ok' => true, 'event_ids' => $results[0]['event_ids']], $results[0]);
        self::assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('events', 2);
        $this->assertDatabaseCount('outbox_messages', 2);
        $record = DB::table('idempotency_records')->where('operation', 'event.ingest.batch')->first();
        self::assertNotNull($record);
        self::assertSame($results[0]['event_ids'], json_decode((string) $record->result_payload, true, 512, JSON_THROW_ON_ERROR)['event_ids']);
    }

    #[Test]
    public function equivalent_external_ids_converge_on_one_event_and_outbox_record(): void
    {
        $fixture = $this->fixture();
        $base = ['operation' => 'ingest_event_batch', ...$fixture, 'events' => [['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:00Z', 'external_event_id' => 'same', 'payload' => ['message' => 'same']]]];
        $results = $this->executeWorkers([$base + ['idempotency_key' => 'first'], $base + ['idempotency_key' => 'second']]);

        self::assertTrue($results[0]['ok']);
        self::assertTrue($results[1]['ok']);
        self::assertSame($results[0]['event_ids'], $results[1]['event_ids']);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        self::assertSame(2, DB::table('idempotency_records')->where('operation', 'event.ingest.batch')->where('status', 'completed')->count());
    }

    #[Test]
    public function conflicting_external_id_loser_rolls_back_its_unrelated_event_and_outbox(): void
    {
        $fixture = $this->fixture();
        $winner = ['operation' => 'ingest_event_batch', ...$fixture, 'idempotency_key' => 'winner', 'events' => [['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:00Z', 'external_event_id' => 'contested', 'payload' => ['message' => 'winner']]]];
        $loser = ['operation' => 'ingest_event_batch', ...$fixture, 'idempotency_key' => 'loser', 'events' => [['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:00Z', 'external_event_id' => 'unrelated', 'payload' => ['message' => 'rollback']], ['event_type' => 'application.error', 'schema_version' => '1', 'occurred_at' => '2026-08-11T12:00:00Z', 'external_event_id' => 'contested', 'payload' => ['message' => 'loser']]]];
        $results = $this->executeWorkers([$winner, $loser]);

        self::assertCount(1, array_filter($results, static fn (array $result): bool => $result['ok']));
        self::assertSame('event.external_id_conflict', array_values(array_filter($results, static fn (array $result): bool => ! $result['ok']))[0]['error']);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('outbox_messages', 1);
        self::assertSame(1, DB::table('idempotency_records')->where('operation', 'event.ingest.batch')->where('status', 'completed')->count());
    }

    /** @param list<array<string, mixed>> $instructions @return list<array<string, mixed>> */
    private function executeWorkers(array $instructions): array
    {
        DB::disconnect();
        $workers = array_map($this->worker(...), $instructions);
        foreach ($workers as [$worker]) {
            $worker->start();
        }
        foreach ($workers as [$worker]) {
            foreach ($worker as $output) {
                if (str_contains($output, 'ready')) {
                    break;
                }
            }
        }
        foreach ($workers as [, $input]) {
            $input->write("go\n");
            $input->close();
        }
        $results = [];
        foreach ($workers as [$worker]) {
            $worker->wait();
            $lines = array_values(array_filter(explode("\n", $worker->getOutput())));
            $results[] = json_decode((string) end($lines), true, 512, JSON_THROW_ON_ERROR);
        }
        DB::purge();

        return $results;
    }

    /** @return array{organization_id: string, event_source_id: string} */
    private function fixture(): array
    {
        $id = fn (): string => $this->app->make(UuidV7Generator::class)->generate();
        $organization = $id();
        $project = $id();
        $application = $id();
        $environment = $id();
        $source = $id();
        $now = now();
        DB::table('organizations')->insert(['id' => $organization, 'name' => 'Batch', 'slug' => 'batch-'.$organization, 'timezone' => 'UTC', 'created_by_identity_id' => $id(), 'status' => 'active', 'version' => 1, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('projects')->insert(['id' => $project, 'organization_id' => $organization, 'name' => 'Batch', 'slug' => 'batch-'.$project, 'status' => 'active', 'created_by_identity_id' => $id(), 'created_at' => $now, 'updated_at' => $now, 'version' => 1]);
        DB::table('applications')->insert(['id' => $application, 'organization_id' => $organization, 'project_id' => $project, 'name' => 'Batch', 'slug' => 'batch-'.$application, 'criticality' => 'high', 'status' => 'active', 'created_by_identity_id' => $id(), 'created_at' => $now, 'updated_at' => $now, 'version' => 1]);
        DB::table('environments')->insert(['id' => $environment, 'organization_id' => $organization, 'application_id' => $application, 'name' => 'Batch', 'slug' => 'batch-'.$environment, 'environment_type' => 'production', 'status' => 'active', 'created_by_identity_id' => $id(), 'created_at' => $now, 'updated_at' => $now, 'version' => 1]);
        DB::table('event_sources')->insert(['id' => $source, 'organization_id' => $organization, 'project_id' => $project, 'application_id' => $application, 'environment_id' => $environment, 'source_type' => 'api_client', 'name' => 'Batch', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now, 'version' => 1]);

        return compact('organization', 'source') + ['organization_id' => $organization, 'event_source_id' => $source];
    }

    /** @param array<string, mixed> $instruction @return array{Process, InputStream} */
    private function worker(array $instruction): array
    {
        $input = new InputStream;

        return [new Process(['php', base_path('tests/Support/ConcurrentLaravelWorker.php')], base_path(), ['SENTINEL_CONCURRENCY_INSTRUCTION' => json_encode($instruction, JSON_THROW_ON_ERROR)] + $_ENV, $input), $input];
    }
}
