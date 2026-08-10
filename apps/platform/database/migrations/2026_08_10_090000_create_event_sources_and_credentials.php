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
        Schema::table('applications', function (Blueprint $table): void {
            $table->unique(['organization_id', 'project_id', 'id'], 'applications_organization_project_id_unique');
        });

        Schema::table('environments', function (Blueprint $table): void {
            $table->unique(['organization_id', 'application_id', 'id'], 'environments_organization_application_id_unique');
        });

        Schema::create('event_sources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('project_id');
            $table->uuid('application_id');
            $table->uuid('environment_id');
            $table->string('source_type');
            $table->string('name');
            $table->string('status');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->unsignedBigInteger('version');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'project_id', 'application_id'])
                ->references(['organization_id', 'project_id', 'id'])
                ->on('applications')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'application_id', 'environment_id'])
                ->references(['organization_id', 'application_id', 'id'])
                ->on('environments')
                ->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'application_id', 'environment_id']);
        });

        Schema::create('event_source_credentials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('event_source_id');
            $table->string('secret_hash');
            $table->string('status');
            $table->timestampTz('created_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->foreign(['organization_id', 'event_source_id'])
                ->references(['organization_id', 'id'])
                ->on('event_sources')
                ->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->index(['organization_id', 'event_source_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX event_source_credentials_one_active_per_source ON event_source_credentials (organization_id, event_source_id) WHERE status = 'active'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS event_source_credentials_one_active_per_source');

        Schema::dropIfExists('event_source_credentials');
        Schema::dropIfExists('event_sources');

        Schema::table('environments', function (Blueprint $table): void {
            $table->dropUnique('environments_organization_application_id_unique');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropUnique('applications_organization_project_id_unique');
        });
    }
};
