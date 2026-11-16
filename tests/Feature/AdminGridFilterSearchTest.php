<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Family;
use App\Models\Product;
use App\Models\Subfamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminGridFilterSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_search_and_filters_narrow_results(): void
    {
        $role = Role::findOrCreate((string) config('admin.super_admin_roles.0', 'super-admin'), 'admin');
        $this->actingAs(AdminUser::factory()->create()->assignRole($role), 'admin');

        $fam = Family::create(['name' => 'Bebidas']);
        $s1 = Subfamily::create(['family_id' => $fam->id, 'name' => 'Refrescos']);
        $s2 = Subfamily::create(['family_id' => $fam->id, 'name' => 'Aguas']);
        Product::create(['sku' => 'SKU-COLA', 'name' => 'Cola Zero', 'subfamily_id' => $s1->id, 'price' => 50, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => true]);
        Product::create(['sku' => 'SKU-AGUA', 'name' => 'Agua Mineral', 'subfamily_id' => $s2->id, 'price' => 30, 'tax_rate' => 0, 'is_active' => false, 'is_favorite' => false]);

        $url = route('admin.screens.index', 'products');
        $get = fn (array $q) => $this->get($url.'?'.http_build_query($q))->assertOk()->getContent();

        $html = $get(['q' => 'Cola']);
        $this->assertStringContainsString('Cola Zero', $html);
        $this->assertStringNotContainsString('Agua Mineral', $html);

        $html = $get(['q' => 'SKU-AGUA']);
        $this->assertStringContainsString('Agua Mineral', $html);
        $this->assertStringNotContainsString('Cola Zero', $html);

        $html = $get(['q' => 'Refrescos']);
        $this->assertStringContainsString('Cola Zero', $html);
        $this->assertStringNotContainsString('Agua Mineral', $html);

        $html = $get(['filter' => ['subfamily_id' => $s2->id]]);
        $this->assertStringContainsString('Agua Mineral', $html);
        $this->assertStringNotContainsString('Cola Zero', $html);

        $html = $get(['filter' => ['is_active' => '0']]);
        $this->assertStringContainsString('Agua Mineral', $html);
        $this->assertStringNotContainsString('Cola Zero', $html);

        $html = $get(['filter' => ['is_favorite' => '1']]);
        $this->assertStringContainsString('Cola Zero', $html);
        $this->assertStringNotContainsString('Agua Mineral', $html);

        $html = $get(['q' => 'Bebidas']); // por nombre de familia
        $this->assertStringContainsString('Cola Zero', $html);
        $this->assertStringContainsString('Agua Mineral', $html);

        $html = $get(['q' => 'Cola', 'filter' => ['is_active' => '0']]); // combinados
        $this->assertStringNotContainsString('Cola Zero', $html);
        $this->assertStringNotContainsString('Agua Mineral', $html);
    }

    public function test_grid_filter_form_has_no_nested_forms(): void
    {
        $role = Role::findOrCreate((string) config('admin.super_admin_roles.0', 'super-admin'), 'admin');
        $this->actingAs(AdminUser::factory()->create()->assignRole($role), 'admin');

        foreach (['products', 'locations', 'transactions', 'families', 'subfamilies', 'licenses', 'devices'] as $screen) {
            $html = $this->get(route('admin.screens.index', $screen))->assertOk()->getContent();
            $first = strpos($html, '<form method="GET"');
            $this->assertNotFalse($first, "{$screen}: no hay formulario de filtros");
            $close = strpos($html, '</form>', $first);
            $inside = substr($html, $first + 5, $close - $first - 5);
            $this->assertSame(0, substr_count($inside, '<form'), "{$screen}: hay un <form> anidado dentro del formulario de filtros/búsqueda");
            $this->assertStringContainsString('name="q"', $inside, "{$screen}: el buscador quedó fuera del formulario");
        }
    }
}
