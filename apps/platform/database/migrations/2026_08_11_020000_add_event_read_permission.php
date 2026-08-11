<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ID = '0198f3b6-48ae-7a44-9192-6f8029df5f1f';

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([['id' => self::ID, 'code' => 'event.read', 'capability' => 'event', 'action' => 'read', 'description' => 'Read Event history.', 'risk_level' => 'low', 'created_at' => now()]]);
        $roles = DB::table('organization_roles')->join('organizations', 'organizations.id', '=', 'organization_roles.organization_id')->where('organization_roles.role_key', 'organization_owner')->select(['organization_roles.id as role_id', 'organizations.created_by_identity_id'])->get();
        $assignments = $roles->map(static fn (object $role): array => ['role_id' => $role->role_id, 'permission_id' => self::ID, 'granted_at' => now(), 'granted_by_identity_id' => $role->created_by_identity_id])->all();
        if ($assignments !== []) {
            DB::table('role_permissions')->insertOrIgnore($assignments);
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission_id', self::ID)->delete();
        DB::table('permissions')->where('id', self::ID)->delete();
    }
};
