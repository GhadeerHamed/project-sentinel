<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->string('timezone')->default('UTC');
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('capability');
            $table->string('action');
            $table->text('description');
            $table->string('risk_level');
            $table->timestampTz('created_at');
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestampTz('granted_at');
            $table->uuid('granted_by_identity_id')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->uuid('revoked_by_identity_id')->nullable();
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('organization_roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};
