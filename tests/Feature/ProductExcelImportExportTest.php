<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Family;
use App\Models\Product;
use App\Models\Subfamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductExcelImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_export_products_excel(): void
    {
        $this->get(route('admin.products.excel.export'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_with_permission_can_export_and_import_updates_by_product_id(): void
    {
        Permission::create(['name' => 'products.edit', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'p-edit', 'guard_name' => 'admin']);
        $role->givePermissionTo('products.edit');

        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $fam = Family::create(['name' => 'F']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'S']);

        $product = Product::create([
            'sku' => 'SKU-1',
            'name' => 'Antes',
            'subfamily_id' => $sub->id,
            'price' => 1.00,
            'tax_rate' => 12.00,
            'is_active' => true,
            'is_favorite' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.excel.export'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['product_id', 'sku', 'barcode', 'name', 'description', 'image_url', 'subfamily_id', 'price', 'tax_rate', 'is_active', 'is_favorite'],
            [
                $product->id,
                'SKU-1',
                '',
                'Después',
                '',
                '',
                $sub->id,
                9.99,
                12,
                1,
                1,
            ],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'pxl');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmp);

        $upload = new UploadedFile($tmp, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.excel.import'), ['file' => $upload])
            ->assertRedirect();

        $product->refresh();
        $this->assertSame('Después', $product->name);
        $this->assertSame('9.99', $product->price);
        $this->assertTrue($product->is_favorite);

        @unlink($tmp);
    }

    public function test_import_creates_product_when_product_id_not_found(): void
    {
        Permission::create(['name' => 'products.edit', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'p-edit', 'guard_name' => 'admin']);
        $role->givePermissionTo('products.edit');

        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $fam = Family::create(['name' => 'F2']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'S2']);

        $newId = (string) Str::uuid();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['product_id', 'sku', 'barcode', 'name', 'description', 'image_url', 'subfamily_id', 'price', 'tax_rate', 'is_active', 'is_favorite'],
            [$newId, 'SKU-NUEVO', '7501234567890', 'Nuevo producto', '', '', $sub->id, 3.5, 0, 1, 0],
        ]);

        $tmp = tempnam(sys_get_temp_dir(), 'pxl');
        (new Xlsx($spreadsheet))->save($tmp);

        $upload = new UploadedFile($tmp, 'imp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.excel.import'), ['file' => $upload])
            ->assertRedirect();

        $p = Product::find($newId);
        $this->assertNotNull($p);
        $this->assertSame('Nuevo producto', $p->name);
        $this->assertSame('SKU-NUEVO', $p->sku);
        $this->assertSame('7501234567890', $p->barcode);

        @unlink($tmp);
    }

    public function test_import_appends_rows_without_product_id_and_matches_existing_by_sku(): void
    {
        Permission::create(['name' => 'products.edit', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'p-edit', 'guard_name' => 'admin']);
        $role->givePermissionTo('products.edit');
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $fam = Family::create(['name' => 'Bebidas']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'Refrescos']);
        $existing = Product::create(['sku' => 'SKU-A', 'name' => 'Antes', 'subfamily_id' => $sub->id, 'price' => 1, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);
        Product::create(['sku' => 'SKU-B', 'barcode' => '111', 'name' => 'Otro', 'subfamily_id' => $sub->id, 'price' => 1, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['product_id', 'sku', 'barcode', 'name', 'price', 'tax_rate', 'subfamily'],
            ['', 'SKU-A', '', 'Después', 5, 0, ''],                       // sin id: coincide por SKU y modifica
            ['', 'SKU-NEW', '', 'Nuevo por nombre', 7.5, 18, 'Refrescos'], // append con subfamilia por nombre
            ['', 'SKU-C', '111', 'Barcode repetido', 2, 0, ''],            // error: barcode de otro producto
            ['', 'SKU-D', '', 'Subfamilia inexistente', 2, 0, 'Fantasma'],  // error: subfamilia
        ]);
        $tmp = tempnam(sys_get_temp_dir(), 'pxl');
        (new Xlsx($spreadsheet))->save($tmp);
        $upload = new UploadedFile($tmp, 'imp.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.excel.import'), ['file' => $upload])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHas('import_errors');

        $this->assertSame('Después', $existing->fresh()->name);
        $new = Product::where('sku', 'SKU-NEW')->first();
        $this->assertNotNull($new);
        $this->assertSame($sub->id, $new->subfamily_id);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-C']);
        $this->assertDatabaseMissing('products', ['sku' => 'SKU-D']);

        @unlink($tmp);
    }

    public function test_export_file_can_be_imported_back_unchanged(): void
    {
        Permission::create(['name' => 'products.edit', 'guard_name' => 'admin']);
        $role = Role::create(['name' => 'p-edit', 'guard_name' => 'admin']);
        $role->givePermissionTo('products.edit');
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $fam = Family::create(['name' => 'F3']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'S3']);
        $p = Product::create(['sku' => 'SKU-RT', 'barcode' => '999', 'name' => 'Ida y vuelta', 'subfamily_id' => $sub->id, 'price' => 12.5, 'cost' => 8, 'tax_rate' => 12, 'is_active' => true, 'is_favorite' => true]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.excel.export'));
        $tmp = tempnam(sys_get_temp_dir(), 'pxl');
        file_put_contents($tmp, $response->streamedContent());

        $this->post(route('admin.products.excel.import'), [
            'file' => new UploadedFile($tmp, 'export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect()->assertSessionMissing('import_errors');

        $this->assertDatabaseCount('products', 1);
        $fresh = $p->fresh();
        $this->assertSame('Ida y vuelta', $fresh->name);
        $this->assertSame('8.00', $fresh->cost);
        $this->assertTrue($fresh->is_favorite);

        @unlink($tmp);
    }
}
