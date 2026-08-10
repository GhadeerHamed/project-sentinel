<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ENVIRONMENT_READ_PERMISSION = [
        'id' => '0198f3b6-48ae-7a44-9192-6f8029df5f1b',
        'code' => 'environment.read',
        'capability' => 'environment',
        'action' => 'read',
        'description' => 'List and read Environments within an Application.',
        'risk_level' => 'low',
    ];

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            [...self::ENVIRONMENT_READ_PERMISSION, 'created_at' => now()],
        ]);

        $ownerRoles = DB::table('organization_roles')
            ->join('organizations', 'organizations.id', '=', 'organization_roles.organization_id')
            ->where('organization_roles.role_key', 'organization_owner')
            ->select(['organization_roles.id as role_id', 'organizations.created_by_identity_id'])
            ->get();

        $assignments = $ownerRoles->map(static fn (object $role): array => [
            'role_id' => $role->role_id,
            'permission_id' => self::ENVIRONMENT_READ_PERMISSION['id'],
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
            ->where('permission_id', self::ENVIRONMENT_READ_PERMISSION['id'])
            ->delete();

        DB::table('permissions')
            ->where('code', self::ENVIRONMENT_READ_PERMISSION['code'])
            ->delete();
    }
};
