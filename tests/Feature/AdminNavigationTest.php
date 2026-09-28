<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
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

    public function test_search_entries_follow_permissions(): void
    {
        $admin = $this->admin('dashboard.view', 'products.view');
        $this->actingAs($admin, 'admin');

        $labels = array_column(AdminNavigation::searchEntries($admin), 'label');

        $this->assertContains('Dashboard comercial', $labels);
        $this->assertContains('Productos', $labels);
        $this->assertNotContains('Dashboard técnico', $labels);
        $this->assertNotContains('Transacciones', $labels);
        $this->assertNotContains('Parámetros del sistema', $labels);

        $products = collect(AdminNavigation::searchEntries($admin))->firstWhere('label', 'Productos');
        $this->assertTrue($products['searchable']);
        $this->assertSame(0, $products['searchRank']);
    }

    public function test_sidebar_and_palette_render_the_same_pages(): void
    {
        $this->actingAs($this->admin('dashboard.view', 'products.view'), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.screens.index', 'products'))
            ->assertDontSee(route('admin.screens.index', 'transactions'))
            ->assertSee('adminPalette(', false)
            ->assertDontSee('disabled aria-disabled="true"', false);
    }
}
