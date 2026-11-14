<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\Location;
use App\Models\Product;
use App\Models\Subfamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCatalogFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::findOrCreate((string) config('admin.super_admin_roles.0', 'super-admin'), 'admin');
        $this->actingAs(AdminUser::factory()->create()->assignRole($role), 'admin');
    }

    public function test_redesigned_forms_render_for_create_and_edit(): void
    {
        $family = Family::create(['name' => 'Bebidas']);
        $sub = Subfamily::create(['family_id' => $family->id, 'name' => 'Refrescos']);
        $product = Product::factory()->create(['subfamily_id' => $sub->id]);
        $location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Centro', 'is_active' => true]);
        $device = Device::create(['id' => (string) Str::uuid(), 'location_id' => $location->id, 'device_fingerprint' => 'fp-1', 'is_enabled' => true]);
        $license = License::create(['device_id' => $device->id, 'valid_from' => now()->subDay(), 'valid_to' => now()->addYear(), 'status' => 'ACTIVE']);

        $this->get(route('admin.screens.create', 'products'))->assertOk()->assertSee('Crear producto')->assertSee('Precio de venta');
        $this->get(route('admin.screens.edit', ['products', $product->id]))->assertOk()->assertSee('Guardar cambios');
        $this->get(route('admin.screens.create', 'families'))->assertOk()->assertSee('Crear familia')->assertSee('family_image', false);
        $this->get(route('admin.screens.edit', ['families', $family->id]))->assertOk()->assertSee('Bebidas');
        $this->get(route('admin.screens.create', 'subfamilies'))->assertOk()->assertSee('Crear subfamilia');
        $this->get(route('admin.screens.edit', ['subfamilies', $sub->id]))->assertOk()->assertSee('Refrescos');
        $this->get(route('admin.screens.create', 'licenses'))->assertOk()->assertSee('Crear licencia')->assertSee('fp-1');
        $this->get(route('admin.screens.edit', ['licenses', $license->getKey()]))->assertOk()->assertSee('Guardar cambios');
    }

    public function test_redesigned_forms_save(): void
    {
        Storage::fake('public');
        $family = Family::create(['name' => 'Snacks']);
        $location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Centro', 'is_active' => true]);
        $device = Device::create(['id' => (string) Str::uuid(), 'location_id' => $location->id, 'device_fingerprint' => 'fp-2', 'is_enabled' => true]);

        $this->post(route('admin.screens.store', 'subfamilies'), ['family_id' => $family->id, 'name' => 'Papas'])
            ->assertRedirect(route('admin.screens.index', 'subfamilies'));
        $sub = Subfamily::where('name', 'Papas')->firstOrFail();

        $this->post(route('admin.screens.store', 'products'), [
            'sku' => 'SKU-F1', 'name' => 'Papas fritas', 'subfamily_id' => $sub->id, 'price' => '45.50', 'cost' => '30', 'tax_rate' => '18',
            'is_active' => '1', 'is_favorite' => '1', 'remove_image' => '0',
            'product_image' => UploadedFile::fake()->image('p.png', 200, 200),
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.screens.index', 'products'));
        $product = Product::where('sku', 'SKU-F1')->firstOrFail();
        $this->assertTrue($product->is_favorite);
        $this->assertNotNull($product->image_url);

        // Quitar la imagen desde la edición
        $this->put(route('admin.screens.update', ['products', $product->id]), [
            'sku' => 'SKU-F1', 'name' => 'Papas fritas', 'subfamily_id' => $sub->id, 'price' => '45.50', 'tax_rate' => '18',
            'is_active' => '0', 'is_favorite' => '0', 'remove_image' => '1',
        ])->assertSessionHasNoErrors();
        $product->refresh();
        $this->assertNull($product->image_url);
        $this->assertFalse($product->is_active);

        $this->post(route('admin.screens.store', 'licenses'), [
            'device_id' => $device->id, 'valid_from' => now()->format('Y-m-d\TH:i'), 'valid_to' => now()->addYear()->format('Y-m-d\TH:i'), 'status' => 'ACTIVE',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.screens.index', 'licenses'));
        $this->assertDatabaseHas('licenses', ['device_id' => $device->id, 'status' => 'ACTIVE']);
    }
}
