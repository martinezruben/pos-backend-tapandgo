<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Family;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'audit_log.view', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'audit-viewer', 'guard_name' => 'admin']);
        $role->givePermissionTo('audit_log.view');
        $this->admin = AdminUser::factory()->create();
        $this->admin->assignRole($role);
        $this->actingAs($this->admin, 'admin');
    }

    public function test_product_creation_is_audited(): void
    {
        $family = Family::create(['name' => 'Bebidas']);
        $product = Product::create([
            'sku' => 'SKU-AUD',
            'name' => 'Cola',
            'subfamily_id' => $family->id ? null : null,
            'price' => 1.5,
            'tax_rate' => 12,
            'is_active' => true,
        ]);

        $log = AdminAuditLog::query()
            ->where('action', 'created')
            ->where('entity_type', 'Product')
            ->where('entity_id', $product->id)
            ->first();

        $this->assertNotNull($log, 'Debe registrar la creación del producto');
        $this->assertSame((string) $this->admin->id, (string) $log->admin_user_id);
        $this->assertSame($this->admin->name, $log->admin_name);
    }

    public function test_product_update_records_field_changes(): void
    {
        $product = Product::create([
            'sku' => 'SKU-AUD2',
            'name' => 'Cola',
            'price' => 1.5,
            'tax_rate' => 12,
            'is_active' => true,
        ]);
        AdminAuditLog::query()->delete();

        $product->update(['price' => 2.5]);

        $log = AdminAuditLog::query()->where('action', 'updated')->where('entity_type', 'Product')->latest('created_at')->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('price', $log->changes);
        $this->assertEquals(1.5, $log->changes['price'][0]);
        $this->assertEquals(2.5, $log->changes['price'][1]);
    }

    public function test_sensitive_attributes_are_not_recorded(): void
    {
        $admin = AdminUser::factory()->create(['password' => bcrypt('Secret0!xyz')]);
        AdminAuditLog::query()->delete();

        $admin->update(['password' => bcrypt('Nueva0!clave'), 'name' => 'Nuevo Nombre']);

        $log = AdminAuditLog::query()->where('action', 'updated')->where('entity_type', 'AdminUser')->first();
        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->changes ?? []);
        $this->assertArrayHasKey('name', $log->changes);
    }

    public function test_delete_is_audited(): void
    {
        $family = Family::create(['name' => 'Temporal']);
        AdminAuditLog::query()->delete();

        $family->delete();

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'deleted',
            'entity_type' => 'Family',
            'entity_id' => $family->id,
        ]);
    }

    public function test_admin_login_is_audited(): void
    {
        // el setUp ya autenticó al admin; cerrar sesión para poder hacer login
        auth('admin')->logout();
        AdminAuditLog::query()->delete();

        $this->post('/admin/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'login',
            'entity_type' => 'AdminUser',
            'admin_user_id' => $this->admin->id,
        ]);
    }

    public function test_audit_log_screen_lists_entries_with_filters(): void
    {
        $product = Product::create([
            'sku' => 'SKU-SCR',
            'name' => 'Cola',
            'price' => 1,
            'tax_rate' => 12,
            'is_active' => true,
        ]);

        $res = $this->get(route('admin.screens.index', 'audit-log'));
        $res->assertOk()->assertSee($product->id)->assertSee('Creación');

        // filtro por entidad Product
        $res = $this->get(route('admin.screens.index', 'audit-log').'?'.http_build_query([
            'filter' => ['entity_type' => 'Product'],
        ]));
        $res->assertOk()->assertSee('Product');
    }
}
