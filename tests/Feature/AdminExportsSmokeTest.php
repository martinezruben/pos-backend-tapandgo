<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Location;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Cada exportable del panel descarga un archivo no vacío del tipo esperado, con datos reales.
 */
class AdminExportsSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::findOrCreate((string) config('admin.super_admin_roles.0', 'super-admin'), 'admin');
        $admin = AdminUser::factory()->create();
        $admin->assignRole($role);
        $this->actingAs($admin, 'admin');

        $this->location = Location::create(['id' => (string) Str::uuid(), 'name' => 'Centro', 'is_active' => true]);
        $product = Product::factory()->create(['name' => 'Café', 'cost' => 4, 'price' => 10]);
        $tx = Transaction::factory()->create([
            'location_id' => $this->location->id,
            'status' => 'PAID',
            'total' => 10,
            'occurred_at' => now()->subHour(),
        ]);
        TransactionItem::factory()->create(['transaction_id' => $tx->id, 'product_id' => $product->id]);
    }

    /** @return array<string, array{string, string, string, string}> [método, ruta, contenido esperado, tipo] */
    public static function exports(): array
    {
        $xlsx = 'spreadsheetml';
        $out = [];
        foreach (['products-best-sellers', 'payment-methods', 'users-performance'] as $r) {
            $out["$r xlsx"] = ['post', "/admin/reports/$r/export", $xlsx, 'range'];
            $out["$r csv"] = ['post', "/admin/reports/$r/export-csv", 'text/csv', 'range'];
            $out["$r pdf"] = ['post', "/admin/reports/$r/export-pdf", 'application/pdf', 'range'];
        }
        foreach (['products-margin', 'products-no-movement'] as $r) {
            $out["$r xlsx"] = ['post', "/admin/reports/$r/export", $xlsx, 'range'];
            $out["$r csv"] = ['post', "/admin/reports/$r/export-csv", 'text/csv', 'range'];
        }
        $out['transacciones xlsx'] = ['post', '/admin/transactions/excel/export', $xlsx, 'range'];
        $out['cierre de caja'] = ['get', '/admin/cierre-caja/export', $xlsx, 'none'];
        $out['productos xlsx'] = ['get', '/admin/products/excel/export', $xlsx, 'none'];

        return $out;
    }

    #[DataProvider('exports')]
    public function test_export_downloads_a_file(string $method, string $url, string $type, string $params): void
    {
        $payload = $params === 'range'
            ? ['date_from' => now()->subDays(5)->toDateString(), 'date_to' => now()->toDateString(), 'location_id' => $this->location->id]
            : [];

        $response = $method === 'post' ? $this->post($url, $payload) : $this->get($url, $payload);

        $response->assertOk();
        $this->assertStringContainsString($type, (string) $response->headers->get('content-type'));
        $content = $response->streamedContent();
        $this->assertGreaterThan(20, strlen($content), 'El archivo exportado está vacío.');
    }

    public function test_transactions_report_export_downloads_a_file(): void
    {
        $response = $this->post('/admin/transactions/report/export', [
            'date_from' => now()->subDays(5)->toDateString(),
            'date_to' => now()->toDateString(),
            'location_id' => $this->location->id,
        ]);

        $response->assertOk();
        $this->assertGreaterThan(100, strlen($response->streamedContent()));
    }
}
