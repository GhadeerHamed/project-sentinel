<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_users', function (Blueprint $table): void {
            $table->uuid('identity_id')->primary();
            $table->string('email')->unique();
            $table->string('display_name');
            $table->string('status');
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->uuid('created_by_identity_id');
            $table->string('status');
            $table->unsignedInteger('version');
            $table->timestampsTz();
            $table->index('created_by_identity_id');
        });

        Schema::create('memberships', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_identity_id');
            $table->string('status');
            $table->timestampTz('activated_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestampTz('removed_at')->nullable();
            $table->unsignedInteger('version');
            $table->timestampsTz();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'user_identity_id']);
            $table->index(['organization_id', 'status']);
        });

        Schema::create('organization_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('role_key');
            $table->timestampsTz();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'role_key']);
            $table->unique(['organization_id', 'id']);
        });

        Schema::create('role_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('identity_id');
            $table->uuid('role_id');
            $table->uuid('assigned_by_identity_id');
            $table->timestampTz('assigned_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('revoked_by_identity_id')->nullable();
            $table->timestampsTz();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'role_id'])
                ->references(['organization_id', 'id'])
                ->on('organization_roles')
                ->cascadeOnDelete();
            $table->index(['organization_id', 'identity_id']);
        });

        Schema::create('outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->uuid('event_id');
            $table->string('event_type');
            $table->unsignedInteger('event_version');
            $table->string('aggregate_type');
            $table->uuid('aggregate_id');
            $table->unsignedInteger('aggregate_version');
            $table->json('payload');
            $table->timestampTz('occurred_at');
            $table->timestampTz('created_at');
            $table->uuid('correlation_id');
            $table->uuid('causation_id')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('status')->default('pending');
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->index(['status', 'published_at']);
            $table->index(['organization_id', 'event_type']);
        });

        Schema::create('audit_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->string('actor_type');
            $table->uuid('actor_id')->nullable();
            $table->string('action');
            $table->string('resource_type');
            $table->uuid('resource_id')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestampTz('recorded_at');
            $table->uuid('correlation_id');
            $table->uuid('causation_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->nullOnDelete();
            $table->index(['organization_id', 'occurred_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_records');
        Schema::dropIfExists('outbox_messages');
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('organization_roles');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('identity_users');
    }
};
