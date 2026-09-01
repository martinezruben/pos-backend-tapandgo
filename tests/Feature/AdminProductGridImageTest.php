<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Family;
use App\Models\Product;
use App\Models\Subfamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProductGridImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'products.view', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'product-viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('products.view');
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');
    }

    public function test_products_grid_shows_thumbnail_column_for_imaged_products(): void
    {
        $family = Family::create(['name' => 'Bebidas']);
        $sub = Subfamily::create(['family_id' => $family->id, 'name' => 'Gaseosas']);
        $withImg = Product::create([
            'sku' => 'SKU-IMG',
            'name' => 'Cola',
            'subfamily_id' => $sub->id,
            'price' => 1.5,
            'tax_rate' => 12,
            'is_active' => true,
            'image_url' => 'https://example.com/cola.jpg',
        ]);
        Product::create([
            'sku' => 'SKU-NOIMG',
            'name' => 'Agua',
            'subfamily_id' => $sub->id,
            'price' => 1,
            'tax_rate' => 12,
            'is_active' => true,
        ]);

        $html = $this->get(route('admin.screens.index', 'products'))->getContent();

        // columna de imagen visible y con miniatura para el producto con imagen
        $this->assertStringContainsString('Imagen', $html);
        $this->assertStringContainsString(
            '<img src="https://example.com/cola.jpg"',
            $html,
        );
        // el producto sin imagen muestra el placeholder, no un img roto
        $this->assertStringContainsString('SKU-NOIMG', $html);
        $this->assertStringNotContainsString('src=""', $html);
    }
}
