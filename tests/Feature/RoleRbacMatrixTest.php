<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleRbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_rbac_matrix(): void
    {
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'admin']);

        $this->get(route('admin.rbac.matrix.edit', $role))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_without_roles_edit_cannot_access_matrix(): void
    {
        Role::create(['name' => 'empty', 'guard_name' => 'admin']);
        $admin = AdminUser::factory()->create();
        $admin->assignRole('empty');

        $role = Role::create(['name' => 'target', 'guard_name' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.rbac.matrix.edit', $role))
            ->assertForbidden();
    }

    public function test_admin_with_roles_edit_can_view_and_update_permissions(): void
    {
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'admin']);
        Permission::create(['name' => 'locations.view', 'guard_name' => 'admin']);
        Permission::create(['name' => 'locations.edit', 'guard_name' => 'admin']);

        $editorRole = Role::create(['name' => 'rbac-editor', 'guard_name' => 'admin']);
        $editorRole->givePermissionTo('roles.edit');

        $admin = AdminUser::factory()->create();
        $admin->assignRole($editorRole);

        $target = Role::create(['name' => 'ops-test', 'guard_name' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.rbac.matrix.edit', $target))
            ->assertOk()
            ->assertSee('Permisos por rol');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.rbac.matrix.update', $target), [
                'permissions' => ['locations.view', 'locations.edit'],
            ])
            ->assertRedirect(route('admin.rbac.matrix.edit', $target))
            ->assertSessionHas('status');

        $target->refresh();
        $this->assertTrue($target->hasPermissionTo('locations.view'));
        $this->assertTrue($target->hasPermissionTo('locations.edit'));
        $this->assertFalse($target->hasPermissionTo('locations.delete'));
    }

    public function test_index_redirects_to_first_role(): void
    {
        Permission::create(['name' => 'roles.edit', 'guard_name' => 'admin']);

        $editorRole = Role::create(['name' => 'rbac-editor', 'guard_name' => 'admin']);
        $editorRole->givePermissionTo('roles.edit');

        $admin = AdminUser::factory()->create();
        $admin->assignRole($editorRole);

        Role::create(['name' => 'alpha', 'guard_name' => 'admin']);
        $second = Role::create(['name' => 'beta', 'guard_name' => 'admin']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.rbac.matrix.index'))
            ->assertRedirect(route('admin.rbac.matrix.edit', Role::where('name', 'alpha')->first()));
    }
}
