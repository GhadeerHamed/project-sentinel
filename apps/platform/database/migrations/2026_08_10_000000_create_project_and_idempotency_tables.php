<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROJECT_CREATE_PERMISSION = [
        'id' => '0198f3b6-48ae-7a44-9192-6f8029df5f14',
        'code' => 'project.create',
        'capability' => 'project',
        'action' => 'create',
        'description' => 'Create Projects within an Organization.',
        'risk_level' => 'medium',
    ];

    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status');
            $table->uuid('created_by_identity_id');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->timestampTz('archived_at')->nullable();
            $table->unsignedBigInteger('version');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'created_at']);
        });

        Schema::create('idempotency_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('identity_id');
            $table->string('operation');
            $table->string('idempotency_key');
            $table->char('request_hash', 64);
            $table->string('status');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->uuid('response_reference')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('expires_at')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'identity_id', 'operation', 'idempotency_key']);
            $table->index(['organization_id', 'expires_at']);
        });

        DB::table('permissions')->insertOrIgnore([
            [...self::PROJECT_CREATE_PERMISSION, 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('code', self::PROJECT_CREATE_PERMISSION['code'])
            ->delete();

        Schema::dropIfExists('idempotency_records');
        Schema::dropIfExists('projects');
    }
};
