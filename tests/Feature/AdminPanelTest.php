<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_can_login_and_access_dashboard(): void
    {
        $permission = Permission::create(['name' => 'locations.view', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'test-admin', 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);

        $admin = AdminUser::factory()->create([
            'email' => 'admin.test@example.com',
        ]);
        $admin->assignRole($role);

        $response = $this->post('/admin/login', [
            'email' => 'admin.test@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin, 'admin');

        $this->get(route('admin.dashboard'))->assertOk();
    }
}
