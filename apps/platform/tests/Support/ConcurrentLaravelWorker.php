<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

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
    $exists = DB::table((string) $instruction['table'])->where('id', (string) $instruction['id'])->exists();
    fwrite(STDOUT, json_encode(['ok' => true, 'exists' => $exists], JSON_THROW_ON_ERROR)."\n");
} catch (Throwable $exception) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'worker_failed'], JSON_THROW_ON_ERROR)."\n");
    exit(1);
}
