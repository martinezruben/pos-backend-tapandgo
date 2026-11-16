<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\CatalogImport;
use App\Models\Family;
use App\Models\Product;
use App\Models\Subfamily;
use App\Services\CatalogExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        foreach (['products.edit', 'families.edit', 'subfamilies.edit'] as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'admin']);
        }
        $role = Role::create(['name' => 'p-edit', 'guard_name' => 'admin']);
        $role->givePermissionTo(['products.edit', 'families.edit', 'subfamilies.edit']);
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        $fam = Family::create(['name' => 'F3']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'S3']);
        $p = Product::create(['sku' => 'SKU-RT', 'barcode' => '999', 'name' => 'Ida y vuelta', 'subfamily_id' => $sub->id, 'price' => 12.5, 'cost' => 8, 'tax_rate' => 12, 'is_active' => true, 'is_favorite' => true]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.excel.export'));
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'catalogo-'));
        $tmp = tempnam(sys_get_temp_dir(), 'pxl');
        file_put_contents($tmp, $response->streamedContent());

        $this->post(route('admin.products.excel.import'), [
            'file' => new UploadedFile($tmp, 'export.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertRedirect();
        $this->assertNull(session('import_errors'), json_encode(session('import_errors')));

        $this->assertDatabaseCount('products', 1);
        $fresh = $p->fresh();
        $this->assertSame('Ida y vuelta', $fresh->name);
        $this->assertSame('8.00', $fresh->cost);
        $this->assertTrue($fresh->is_favorite);

        @unlink($tmp);
    }

    private function catalogAdmin(array $perms = ['products.edit', 'families.edit', 'subfamilies.edit']): AdminUser
    {
        $role = Role::create(['name' => 'cat-'.Str::random(5), 'guard_name' => 'admin']);
        foreach ($perms as $perm) {
            Permission::findOrCreate($perm, 'admin');
            $role->givePermissionTo($perm);
        }
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    private function workbook(array $sheets): UploadedFile
    {
        $book = new Spreadsheet;
        $first = true;
        foreach ($sheets as $title => $rows) {
            $sheet = $first ? $book->getActiveSheet() : $book->createSheet();
            $sheet->setTitle($title);
            $sheet->fromArray($rows);
            $first = false;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'cat');
        (new Xlsx($book))->save($tmp);

        return new UploadedFile($tmp, 'catalogo.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_export_has_all_sheets_and_no_promotions(): void
    {
        $admin = $this->catalogAdmin();
        $fam = Family::create(['name' => 'Bebidas']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'Refrescos']);
        Product::create(['sku' => 'S1', 'name' => 'Cola', 'subfamily_id' => $sub->id, 'price' => 50, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.excel.export'));
        $tmp = tempnam(sys_get_temp_dir(), 'cat');
        file_put_contents($tmp, $response->streamedContent());
        $book = IOFactory::load($tmp);

        $this->assertSame(['Productos', 'Subfamilias', 'Familias', 'Listas', 'Instrucciones'], $book->getSheetNames());
        $products = $book->getSheetByName('Productos')->toArray();
        $this->assertContains('family', $products[0]);
        $this->assertSame('Bebidas', $products[1][array_search('family', $products[0])]);
        $this->assertSame('Refrescos', $book->getSheetByName('Subfamilias')->toArray()[1][2]);
        $this->assertSame('Bebidas', $book->getSheetByName('Familias')->toArray()[1][1]);
        $this->assertNotContains('Promociones', $book->getSheetNames());

        @unlink($tmp);
    }

    public function test_filtered_export_only_includes_matching_products_and_their_hierarchy(): void
    {
        $admin = $this->catalogAdmin();
        $f1 = Family::create(['name' => 'Bebidas']);
        $f2 = Family::create(['name' => 'Snacks']);
        $s1 = Subfamily::create(['family_id' => $f1->id, 'name' => 'Refrescos']);
        $s2 = Subfamily::create(['family_id' => $f2->id, 'name' => 'Papas']);
        Product::create(['sku' => 'A', 'name' => 'Cola', 'subfamily_id' => $s1->id, 'price' => 1, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);
        Product::create(['sku' => 'B', 'name' => 'Papitas', 'subfamily_id' => $s2->id, 'price' => 1, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.products.excel.export', ['scope' => 'filtered', 'filter' => ['subfamily_id' => $s1->id]]));
        $tmp = tempnam(sys_get_temp_dir(), 'cat');
        file_put_contents($tmp, $response->streamedContent());
        $book = IOFactory::load($tmp);

        $filled = fn (string $sheet) => array_values(array_filter($book->getSheetByName($sheet)->toArray(), fn ($r) => array_filter($r, fn ($v) => $v !== null && $v !== '') !== []));
        $this->assertCount(2, $filled('Productos')); // cabecera + 1
        $this->assertCount(2, $filled('Familias'));
        $this->assertSame('Bebidas', $filled('Familias')[1][1]);

        @unlink($tmp);
    }

    public function test_unknown_family_or_subfamily_is_an_error_unless_create_missing_is_on(): void
    {
        $admin = $this->catalogAdmin();
        $file = fn () => $this->workbook(['Productos' => [
            ['sku', 'name', 'price', 'family', 'subfamily'],
            ['N1', 'Nuevo', 5, 'Lácteos', 'Leches'],
        ]]);

        $this->actingAs($admin, 'admin')->post(route('admin.products.excel.import'), ['file' => $file()])->assertSessionHas('import_errors');
        $this->assertDatabaseMissing('products', ['sku' => 'N1']);
        $this->assertDatabaseCount('families', 0);

        $this->post(route('admin.products.excel.import'), ['file' => $file(), 'create_missing' => '1'])->assertSessionMissing('import_errors');
        $product = Product::where('sku', 'N1')->firstOrFail();
        $this->assertSame('Leches', $product->subfamily->name);
        $this->assertSame('Lácteos', $product->subfamily->family->name);
    }

    public function test_multi_sheet_import_creates_hierarchy_in_order(): void
    {
        $admin = $this->catalogAdmin();
        $file = $this->workbook([
            'Productos' => [['sku', 'name', 'price', 'family', 'subfamily'], ['P1', 'Cola', 10, 'Bebidas', 'Refrescos']],
            'Subfamilias' => [['subfamily_id', 'family', 'name'], ['', 'Bebidas', 'Refrescos']],
            'Familias' => [['family_id', 'name', 'description'], ['', 'Bebidas', 'Líquidos']],
        ]);

        $this->actingAs($admin, 'admin')->post(route('admin.products.excel.import'), ['file' => $file])->assertSessionMissing('import_errors');

        $this->assertSame('Líquidos', Family::where('name', 'Bebidas')->value('description'));
        $this->assertSame('Refrescos', Product::where('sku', 'P1')->first()->subfamily->name);
        $this->assertDatabaseCount('families', 1);
        $this->assertDatabaseCount('subfamilies', 1);
    }

    public function test_preview_saves_nothing_and_apply_persists(): void
    {
        Storage::fake('local');
        $admin = $this->catalogAdmin();
        $this->actingAs($admin, 'admin');

        $response = $this->post(route('admin.products.excel.preview'), [
            'create_missing' => '1',
            'file' => $this->workbook(['Productos' => [['sku', 'name', 'price', 'family', 'subfamily'], ['P9', 'Té', 3, 'Infusiones', 'Té verde']]]),
        ])->assertOk()->assertSee('Revisión de la importación')->assertSee('Aplicar importación');

        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('families', 0);

        preg_match('/name="token" value="([^"]+)"/', $response->getContent(), $m);
        $this->post(route('admin.products.excel.apply'), ['token' => $m[1], 'ext' => 'xlsx', 'create_missing' => '1'])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['sku' => 'P9']);
        $this->assertDatabaseHas('families', ['name' => 'Infusiones']);
    }

    public function test_sheets_without_permission_are_ignored_and_moves_are_flagged(): void
    {
        $fam = Family::create(['name' => 'Bebidas']);
        $a = Subfamily::create(['family_id' => $fam->id, 'name' => 'Refrescos']);
        Subfamily::create(['family_id' => $fam->id, 'name' => 'Aguas']);
        Product::create(['sku' => 'M1', 'name' => 'Cola', 'subfamily_id' => $a->id, 'price' => 1, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);

        $admin = $this->catalogAdmin(['products.edit']);
        $result = app(CatalogExcelService::class)->import(
            $this->workbook([
                'Productos' => [['sku', 'name', 'price', 'family', 'subfamily'], ['M1', 'Cola', 1, 'Bebidas', 'Aguas']],
                'Familias' => [['family_id', 'name'], ['', 'Nueva familia']],
            ])->getRealPath(),
            ['dry_run' => true, 'can_families' => false, 'can_subfamilies' => false],
        );

        $this->assertSame(1, $result['moved']);
        $this->assertCount(1, $result['ignored']);
        $this->assertDatabaseMissing('families', ['name' => 'Nueva familia']);
        $this->assertSame($a->id, Product::where('sku', 'M1')->value('subfamily_id')); // dry run: no cambia
    }

    public function test_import_records_stats_price_variations_and_downloadable_report(): void
    {
        Storage::fake('local');
        $admin = $this->catalogAdmin();
        $fam = Family::create(['name' => 'Bebidas']);
        $sub = Subfamily::create(['family_id' => $fam->id, 'name' => 'Refrescos']);
        foreach ([['A1', 'Cola', 100], ['A2', 'Agua', 50], ['A3', 'Jugo', 80]] as [$sku, $n, $price]) {
            Product::create(['sku' => $sku, 'name' => $n, 'subfamily_id' => $sub->id, 'price' => $price, 'tax_rate' => 0, 'is_active' => true, 'is_favorite' => false]);
        }

        $file = $this->workbook(['Productos' => [
            ['sku', 'name', 'price', 'family', 'subfamily'],
            ['A1', 'Cola', 110, 'Bebidas', 'Refrescos'],   // sube 10 %
            ['A2', 'Agua', 45, 'Bebidas', 'Refrescos'],    // baja 10 %
            ['A3', 'Jugo', 80, 'Bebidas', 'Refrescos'],    // sin cambios
            ['A4', 'Té', 30, 'Bebidas', 'Refrescos'],      // nuevo
            ['A5', 'Malo', 'x', 'Bebidas', 'Refrescos'],   // error
        ]]);

        $response = $this->actingAs($admin, 'admin')->post(route('admin.products.excel.import'), ['file' => $file]);
        $import = CatalogImport::firstOrFail();
        $response->assertRedirect(route('admin.products.excel.show', $import));

        $t = $import->summary['totals'];
        $this->assertSame(5, $t['read']);
        $this->assertSame(3, $t['matched']);
        $this->assertSame(1, $t['created']);
        $this->assertSame(2, $t['updated']);
        $this->assertSame(1, $t['unchanged']);
        $this->assertSame(1, $t['errors']);
        $p = $import->summary['price'];
        $this->assertSame(2, $p['changed']);
        $this->assertSame(1, $p['up']);
        $this->assertSame(1, $p['down']);
        $this->assertEquals(0.0, $p['avg_pct']);

        $this->get(route('admin.products.excel.show', $import))->assertOk()->assertSee('Variaciones de precio')->assertSee('Descargar informe detallado');
        $this->get(route('admin.products.excel.history'))->assertOk()->assertSee('catalogo.xlsx');

        $download = $this->get(route('admin.products.excel.report', $import));
        $download->assertOk();
        $tmp = tempnam(sys_get_temp_dir(), 'rep');
        file_put_contents($tmp, $download->streamedContent());
        $book = IOFactory::load($tmp);
        $this->assertSame(['Resumen', 'Productos', 'Variaciones de precio', 'Subfamilias', 'Familias', 'Errores'], $book->getSheetNames());
        $var = $book->getSheetByName('Variaciones de precio')->toArray();
        $this->assertCount(3, $var); // cabecera + 2
        $this->assertSame('A1', $var[1][0]);
        $this->assertEquals(10, $var[1][5]);

        @unlink($tmp);
    }
}
