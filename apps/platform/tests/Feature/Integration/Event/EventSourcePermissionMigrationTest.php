<?php

declare(strict_types=1);

namespace Tests\Feature\Integration\Event;

use App\Infrastructure\Persistence\Organization\Records\OrganizationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Sentinel\Shared\Domain\Identifier\UuidV7Generator;
use Tests\TestCase;

final class EventSourcePermissionMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_backfills_event_source_create_for_existing_organization_owner_roles(): void
    {
        $migration = require database_path('migrations/2026_08_10_100000_add_event_source_create_permission.php');
        $migration->down();

        $identityId = $this->uuid();
        $organizationId = $this->uuid();
        $roleId = $this->uuid();
        $now = now();
        OrganizationRecord::query()->create([
            'id' => $organizationId,
            'name' => 'Existing Owner Organization',
            'slug' => 'existing-owner-organization-'.$organizationId,
            'timezone' => 'UTC',
            'created_by_identity_id' => $identityId,
            'status' => 'active',
            'version' => 1,
        ]);
        DB::table('organization_roles')->insert([
            'id' => $roleId,
            'organization_id' => $organizationId,
            'role_key' => 'organization_owner',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration->up();

        $permissionId = DB::table('permissions')->where('code', 'event_source.create')->value('id');
        self::assertIsString($permissionId);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'granted_by_identity_id' => $identityId,
        ]);
    }

    private function uuid(): string
    {
        return $this->app->make(UuidV7Generator::class)->generate();
    }
}
