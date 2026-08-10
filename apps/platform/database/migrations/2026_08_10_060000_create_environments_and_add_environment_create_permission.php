<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ENVIRONMENT_CREATE_PERMISSION = [
        'id' => '0198f3b6-48ae-7a44-9192-6f8029df5f1a',
        'code' => 'environment.create',
        'capability' => 'environment',
        'action' => 'create',
        'description' => 'Create Environments within an active Application.',
        'risk_level' => 'medium',
    ];

    public function up(): void
    {
        Schema::create('environments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('application_id');
            $table->string('name');
            $table->string('slug');
            $table->string('environment_type');
            $table->string('status');
            $table->uuid('created_by_identity_id');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->timestampTz('archived_at')->nullable();
            $table->unsignedBigInteger('version');
            $table->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign(['organization_id', 'application_id'])->references(['organization_id', 'id'])->on('applications')->cascadeOnDelete();
            $table->unique(['organization_id', 'id']);
            $table->unique(['organization_id', 'application_id', 'slug']);
            $table->index(['organization_id', 'application_id', 'status']);
            $table->index(['organization_id', 'application_id', 'created_at']);
        });

        DB::table('permissions')->insertOrIgnore([
            [...self::ENVIRONMENT_CREATE_PERMISSION, 'created_at' => now()],
        ]);

        $ownerRoles = DB::table('organization_roles')
            ->join('organizations', 'organizations.id', '=', 'organization_roles.organization_id')
            ->where('organization_roles.role_key', 'organization_owner')
            ->select(['organization_roles.id as role_id', 'organizations.created_by_identity_id'])
            ->get();

        $assignments = $ownerRoles->map(static fn (object $role): array => [
            'role_id' => $role->role_id,
            'permission_id' => self::ENVIRONMENT_CREATE_PERMISSION['id'],
            'granted_at' => now(),
            'granted_by_identity_id' => $role->created_by_identity_id,
        ])->all();

        if ($assignments !== []) {
            DB::table('role_permissions')->insertOrIgnore($assignments);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')
            ->where('permission_id', self::ENVIRONMENT_CREATE_PERMISSION['id'])
            ->delete();

        DB::table('permissions')
            ->where('code', self::ENVIRONMENT_CREATE_PERMISSION['code'])
            ->delete();

        Schema::dropIfExists('environments');
    }
};
