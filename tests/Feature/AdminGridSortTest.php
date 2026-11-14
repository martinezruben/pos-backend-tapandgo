<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Device;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminGridSortTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string ...$permissions): AdminUser
    {
        $admin = AdminUser::factory()->create();
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'admin');
            $admin->givePermissionTo($name);
        }

        return $admin;
    }

    public function test_every_grid_column_header_is_a_sort_link(): void
    {
        Product::factory()->create(['name' => 'Café']);

        $html = $this->actingAs($this->admin('products.view'), 'admin')
            ->get(route('admin.screens.index', 'products'))
            ->assertOk()
            ->getContent();

        // Columna que antes no estaba en grid.sortable
        $this->assertStringContainsString('sort=cost', $html);
        $this->assertStringContainsString('aria-sort=', $html);
    }

    public function test_sort_by_column_orders_rows_both_directions(): void
    {
        Product::factory()->create(['name' => 'Bbb', 'price' => 20]);
        Product::factory()->create(['name' => 'Aaa', 'price' => 30]);
        Product::factory()->create(['name' => 'Ccc', 'price' => 10]);
        $this->actingAs($this->admin('products.view'), 'admin');

        $asc = $this->get(route('admin.screens.index', ['products', 'sort' => 'name', 'dir' => 'asc']))->assertOk()->getContent();
        $this->assertTrue(strpos($asc, 'Aaa') < strpos($asc, 'Bbb') && strpos($asc, 'Bbb') < strpos($asc, 'Ccc'));

        $desc = $this->get(route('admin.screens.index', ['products', 'sort' => 'name', 'dir' => 'desc']))->assertOk()->getContent();
        $this->assertTrue(strpos($desc, 'Ccc') < strpos($desc, 'Bbb') && strpos($desc, 'Bbb') < strpos($desc, 'Aaa'));
    }

    public function test_foreign_key_column_sorts_by_related_name(): void
    {
        $zeta = Location::create(['id' => (string) Str::uuid(), 'name' => 'Zeta', 'is_active' => true]);
        $alfa = Location::create(['id' => (string) Str::uuid(), 'name' => 'Alfa', 'is_active' => true]);
        Device::create(['id' => (string) Str::uuid(), 'location_id' => $zeta->id, 'device_fingerprint' => 'fp-z', 'is_enabled' => true]);
        Device::create(['id' => (string) Str::uuid(), 'location_id' => $alfa->id, 'device_fingerprint' => 'fp-a', 'is_enabled' => true]);

        $html = $this->actingAs($this->admin('devices.view'), 'admin')
            ->get(route('admin.screens.index', ['devices', 'sort' => 'location_id', 'dir' => 'asc']))
            ->assertOk()
            ->getContent();

        $this->assertLessThan(strpos($html, 'fp-z'), strpos($html, 'fp-a'));
    }

    public function test_location_form_renders_with_map_picker(): void
    {
        $location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Sucursal Centro', 'latitude' => 18.48, 'longitude' => -69.93, 'is_active' => true]);
        $admin = $this->admin('locations.view', 'locations.edit');
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.screens.create', 'locations'))
            ->assertOk()
            ->assertSee('location-picker-map', false)
            ->assertSee('Crear localidad');

        $this->get(route('admin.screens.edit', ['locations', $location->id]))
            ->assertOk()
            ->assertSee('Guardar cambios')
            ->assertSee('Sucursal Centro');

        $this->post(route('admin.screens.store', 'locations'), [
            'name' => 'Nueva', 'address' => 'Calle 1', 'latitude' => '18.5', 'longitude' => '-69.9', 'is_active' => '1',
        ])->assertRedirect(route('admin.screens.index', 'locations'));
        $this->assertDatabaseHas('locations', ['name' => 'Nueva', 'is_active' => true]);
    }
}
