<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{id: string, code: string, capability: string, action: string, description: string, risk_level: string}>
     */
    private const PERMISSIONS = [
        [
            'id' => '0198f3b6-48ae-7a41-9192-6f8029df5f11',
            'code' => 'organization.member.manage',
            'capability' => 'organization',
            'action' => 'member_manage',
            'description' => 'Manage Organization Memberships.',
            'risk_level' => 'medium',
        ],
        [
            'id' => '0198f3b6-48ae-7a42-9192-6f8029df5f12',
            'code' => 'organization.update',
            'capability' => 'organization',
            'action' => 'update',
            'description' => 'Update Organization settings.',
            'risk_level' => 'medium',
        ],
        [
            'id' => '0198f3b6-48ae-7a43-9192-6f8029df5f13',
            'code' => 'organization.suspend',
            'capability' => 'organization',
            'action' => 'suspend',
            'description' => 'Suspend an Organization.',
            'risk_level' => 'high',
        ],
    ];

    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            ...array_map(
                static fn (array $permission): array => [...$permission, 'created_at' => now()],
                self::PERMISSIONS,
            ),
        ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('code', array_column(self::PERMISSIONS, 'code'))
            ->delete();
    }
};
