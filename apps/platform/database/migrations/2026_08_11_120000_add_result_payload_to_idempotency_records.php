<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('idempotency_records', function (Blueprint $table): void {
            $table->jsonb('result_payload')->nullable()->after('response_reference');
        });
    }

    public function down(): void
    {
        Schema::table('idempotency_records', function (Blueprint $table): void {
            $table->dropColumn('result_payload');
        });
    }
};
