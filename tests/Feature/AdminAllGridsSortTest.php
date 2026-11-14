<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Support\AdminGridQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Todas las pantallas con grid ordenan por cada columna ordenable sin error, en ambos sentidos.
 */
class AdminAllGridsSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_sortable_column_of_every_grid_responds_ok(): void
    {
        $role = Role::findOrCreate((string) config('admin.super_admin_roles.0', 'super-admin'), 'admin');
        $this->actingAs(AdminUser::factory()->create()->assignRole($role), 'admin');

        $checked = 0;
        foreach (config('admin_screens') as $screen => $cfg) {
            if ($screen === 'dashboard' || ! isset($cfg['model'])) {
                continue;
            }
            $url = route('admin.screens.index', $screen);
            $first = $this->get($url);
            if ($first->isRedirect()) {
                continue; // pantallas con vista propia (p. ej. reportes) redirigen
            }
            $first->assertOk();

            foreach (AdminGridQuery::sortableFields($cfg) as $field) {
                foreach (['asc', 'desc'] as $dir) {
                    $response = $this->get($url.'?'.http_build_query(['sort' => $field, 'dir' => $dir]));
                    $this->assertSame(200, $response->getStatusCode(), "Falló ordenar {$screen} por {$field} {$dir}");
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(50, $checked);
    }
}
