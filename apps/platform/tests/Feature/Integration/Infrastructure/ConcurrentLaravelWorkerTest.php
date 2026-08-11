<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Infrastructure;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class ConcurrentLaravelWorkerTest extends TestCase
{
    use DatabaseMigrations;

    #[Test]
    public function independently_booted_workers_observe_committed_state_after_a_pipe_barrier(): void
    {
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL concurrency coverage requires DB_CONNECTION=pgsql.');
        }
        $id = $this->app->make(UuidV7Generator::class)->generate();
        DB::table('organizations')->insert(['id' => $id, 'name' => 'Concurrency', 'slug' => 'concurrency-'.$id, 'timezone' => 'UTC', 'created_by_identity_id' => $id, 'status' => 'active', 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::disconnect();
        $workers = [$this->worker($id), $this->worker($id)];
        foreach ($workers as [$worker]) {
            $worker->start();
        }
        foreach ($workers as [$worker]) {
            $ready = false;
            foreach ($worker as $output) {
                if (str_contains($output, 'ready')) {
                    $ready = true;

                    break;
                }
            }
            self::assertTrue($ready);
        }
        foreach ($workers as [, $input]) {
            $input->write("go\n");
            $input->close();
        }
        foreach ($workers as [$worker]) {
            $worker->wait();
            self::assertTrue($worker->isSuccessful());
            $lines = array_values(array_filter(explode("\n", $worker->getOutput())));
            self::assertSame(['ok' => true, 'exists' => true], json_decode((string) end($lines), true, 512, JSON_THROW_ON_ERROR));
        }
    }

    /** @return array{Process, InputStream} */
    private function worker(string $id): array
    {
        $input = new InputStream;

        return [new Process(['php', base_path('tests/Support/ConcurrentLaravelWorker.php')], base_path(), ['SENTINEL_CONCURRENCY_INSTRUCTION' => json_encode(['table' => 'organizations', 'id' => $id], JSON_THROW_ON_ERROR)] + $_ENV, $input), $input];
    }
}
