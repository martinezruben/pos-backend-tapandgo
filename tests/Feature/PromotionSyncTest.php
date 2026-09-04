<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\Location;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PromotionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_pull_includes_promotions_with_scope(): void
    {
        $location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Main', 'is_active' => true]);
        $device = Device::create([
            'id' => (string) Str::uuid(),
            'location_id' => $location->id,
            'device_fingerprint' => 'fp-promo',
            'is_enabled' => true,
        ]);
        License::create([
            'device_id' => $device->id,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'status' => 'ACTIVE',
        ]);

        $family = Family::create(['name' => 'Bebidas']);
        $global = Promotion::create([
            'name' => 'Black Friday',
            'type' => 'PERCENT',
            'value' => 10,
            'starts_at' => '2026-11-27 00:00:00',
            'ends_at' => '2026-11-30 23:59:59',
            'is_active' => true,
        ]);
        $familyPromo = Promotion::create([
            'name' => 'Bebidas 2x precio oferta',
            'type' => 'PRICE',
            'value' => 0.99,
            'family_id' => $family->id,
            'is_active' => true,
        ]);

        $token = $device->createToken('promo-test')->plainTextToken;
        $res = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sync/pull?'.http_build_query(['device_fingerprint' => $device->device_fingerprint]))
            ->assertOk();

        $promos = collect($res->json('data.promotions'));

        $globalRow = $promos->firstWhere('id', $global->id);
        $this->assertNotNull($globalRow);
        $this->assertSame('PERCENT', $globalRow['type']);
        $this->assertSame('GLOBAL', $globalRow['scopeType']);
        $this->assertNull($globalRow['scopeId']);

        $familyRow = $promos->firstWhere('id', $familyPromo->id);
        $this->assertNotNull($familyRow);
        $this->assertSame('FAMILY', $familyRow['scopeType']);
        $this->assertSame($family->id, $familyRow['scopeId']);
        $this->assertSame('Bebidas', $familyRow['scopeName']);
    }

    public function test_pull_promotions_delta_and_tombstones(): void
    {
        $location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Main', 'is_active' => true]);
        $device = Device::create([
            'id' => (string) Str::uuid(),
            'location_id' => $location->id,
            'device_fingerprint' => 'fp-promo-delta',
            'is_enabled' => true,
        ]);
        License::create([
            'device_id' => $device->id,
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addDay(),
            'status' => 'ACTIVE',
        ]);

        $promo = Promotion::create(['name' => 'Vigente', 'type' => 'AMOUNT', 'value' => 5, 'is_active' => true]);
        $toRemove = Promotion::create(['name' => 'Por eliminar', 'type' => 'PERCENT', 'value' => 15, 'is_active' => true]);

        $token = $device->createToken('promo-delta')->plainTextToken;

        // primera sync completa: solo no eliminadas
        $res1 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sync/pull?'.http_build_query(['device_fingerprint' => $device->device_fingerprint]))
            ->assertOk();
        $this->assertCount(2, $res1->json('data.promotions'));
        $ts = $res1->json('syncTimestamp');

        $this->travel(5)->seconds();

        // delta: la edición y el tombstone nuevo (deleted_at > ts)
        $promo->update(['value' => 7]);
        $toRemove->delete();
        $res2 = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/sync/pull?'.http_build_query([
                'device_fingerprint' => $device->device_fingerprint,
                'lastSyncTimestamp' => $ts,
            ]))
            ->assertOk();

        $rows = collect($res2->json('data.promotions'));
        $this->assertContains($promo->id, $rows->pluck('id'));
        $tombstone = $rows->firstWhere('id', $toRemove->id);
        $this->assertNotNull($tombstone, 'El borrado posterior al delta debe llegar como tombstone');
        $this->assertNotNull($tombstone['deletedAt']);
    }

    public function test_admin_can_create_promotion_via_screen(): void
    {
        Permission::create(['name' => 'promotions.view', 'guard_name' => 'admin']);
        Permission::create(['name' => 'promotions.edit', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'promo-manager', 'guard_name' => 'admin']);
        $role->givePermissionTo(['promotions.view', 'promotions.edit']);
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.screens.store', 'promotions'), [
                'name' => 'Happy Hour',
                'type' => 'PERCENT',
                'value' => '20',
                'starts_at' => '2026-12-01T10:00',
                'ends_at' => '2026-12-15T22:00',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.screens.index', 'promotions'));

        $promo = Promotion::where('name', 'Happy Hour')->first();
        $this->assertNotNull($promo);
        $this->assertSame('PERCENT', $promo->type);
        $this->assertTrue((bool) $promo->is_active);

        User::factory()->create();
    }
}
