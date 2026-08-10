<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_sources', function (Blueprint $table): void {
            $table->unique(
                ['organization_id', 'project_id', 'application_id', 'environment_id', 'id'],
                'event_sources_scope_identity_unique',
            );
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('project_id');
            $table->uuid('application_id');
            $table->uuid('environment_id');
            $table->uuid('event_source_id');
            $table->string('external_event_id')->nullable();
            $table->string('event_type');
            $table->string('category');
            $table->string('severity')->nullable();
            $table->string('schema_version');
            $table->timestampTz('occurred_at');
            $table->timestampTz('received_at');
            $table->jsonb('payload');
            $table->jsonb('attributes')->default(DB::raw("'{}'::jsonb"));
            $table->uuid('correlation_id')->nullable();
            $table->uuid('causation_id')->nullable();
            $table->string('trace_id')->nullable();
            $table->char('normalized_event_hash', 64);
            $table->timestampTz('created_at');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'project_id', 'application_id'])
                ->references(['organization_id', 'project_id', 'id'])
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'application_id', 'environment_id'])
                ->references(['organization_id', 'application_id', 'id'])
                ->on('environments')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'project_id', 'application_id', 'environment_id', 'event_source_id'])
                ->references(['organization_id', 'project_id', 'application_id', 'environment_id', 'id'])
                ->on('event_sources')
                ->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'occurred_at', 'id']);
            $table->index(['organization_id', 'application_id', 'occurred_at']);
            $table->index(['organization_id', 'environment_id', 'occurred_at']);
            $table->index(['organization_id', 'event_type', 'occurred_at']);
            $table->index(['organization_id', 'category', 'severity', 'occurred_at']);
            $table->index(['organization_id', 'correlation_id']);
        });

        DB::statement('CREATE UNIQUE INDEX events_source_external_event_id_unique ON events (organization_id, event_source_id, external_event_id) WHERE external_event_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS events_source_external_event_id_unique');

        Schema::dropIfExists('events');

        Schema::table('event_sources', function (Blueprint $table): void {
            $table->dropUnique('event_sources_scope_identity_unique');
        });
    }
};
