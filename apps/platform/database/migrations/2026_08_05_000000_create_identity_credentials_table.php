<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_credentials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_id');
            $table->uuid('organization_id')->nullable();
            $table->string('credential_type');
            $table->string('secret_reference');
            $table->string('public_identifier')->nullable();
            $table->string('status');
            $table->timestampTz('created_at');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampTz('rotated_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->index(['identity_id', 'credential_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_credentials');
    }
};
