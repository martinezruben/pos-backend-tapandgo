<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Location;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private const PERMISSIONS = [
        'products_best_sellers.view',
        'payment_methods_report.view',
        'users_performance_report.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']);
        }
    }

    protected function createAdminUser()
    {
        $admin = AdminUser::factory()->create();
        $admin->givePermissionTo(self::PERMISSIONS);

        return $admin;
    }

    public function test_products_best_sellers_route_returns_ok()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/products-best-sellers')
            ->assertOk()
            ->assertViewIs('admin.reports.products-best-sellers');
    }

    public function test_products_best_sellers_displays_data()
    {
        $admin = $this->createAdminUser();
        $location = Location::factory()->create();
        $product = Product::factory()->create(['name' => 'Test Product', 'sku' => 'TEST-001']);
        $user = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'location_id' => $location->id,
            'user_id' => $user->id,
            'occurred_at' => now(),
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'qty' => 5,
            'unit_price' => 20.00,
            'line_total' => 100.00,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/products-best-sellers')
            ->assertOk()
            ->assertSee('Test Product')
            ->assertSee('TEST-001');
    }

    public function test_payment_methods_report_route_returns_ok()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/payment-methods')
            ->assertOk()
            ->assertViewIs('admin.reports.payment-methods');
    }

    public function test_payment_methods_displays_data()
    {
        $admin = $this->createAdminUser();
        $location = Location::factory()->create();
        $user = User::factory()->create();

        $transaction = Transaction::factory()->create([
            'location_id' => $location->id,
            'user_id' => $user->id,
            'occurred_at' => now(),
        ]);

        TransactionPayment::factory()->create([
            'transaction_id' => $transaction->id,
            'payment_method' => 'Credit Card',
            'amount' => 150.00,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/payment-methods')
            ->assertOk()
            ->assertSee('Credit Card');
    }

    public function test_users_performance_report_route_returns_ok()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/users-performance')
            ->assertOk()
            ->assertViewIs('admin.reports.users-performance');
    }

    public function test_users_performance_displays_data()
    {
        $admin = $this->createAdminUser();
        $location = Location::factory()->create();
        $user = User::factory()->create(['full_name' => 'John Doe', 'username' => 'johndoe']);

        Transaction::factory()->create([
            'location_id' => $location->id,
            'user_id' => $user->id,
            'total' => 500.00,
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/reports/users-performance')
            ->assertOk()
            ->assertSee('John Doe')
            ->assertSee('johndoe');
    }

    public function test_products_best_sellers_accepts_date_filters()
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/reports/products-best-sellers?date_from=2026-01-01&date_to=2026-12-31')
            ->assertOk();

        $this->assertStringContainsString('2026-01-01', $response->content());
        $this->assertStringContainsString('2026-12-31', $response->content());
    }

    public function test_users_performance_accepts_location_filter()
    {
        $admin = $this->createAdminUser();
        $location = Location::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/reports/users-performance?location_id='.$location->id)
            ->assertOk();

        $this->assertStringContainsString($location->id, $response->content());
    }

    public function test_report_pages_require_permission()
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')->get('/admin/reports/products-best-sellers')->assertForbidden();
        $this->actingAs($admin, 'admin')->get('/admin/reports/payment-methods')->assertForbidden();
        $this->actingAs($admin, 'admin')->get('/admin/reports/users-performance')->assertForbidden();
        $this->actingAs($admin, 'admin')
            ->post('/admin/reports/products-best-sellers/export-csv', ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'])
            ->assertForbidden();
    }

    public function test_generic_screen_route_redirects_to_report_page()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get('/admin/screens/products-best-sellers')
            ->assertRedirect(route('admin.reports.products-best-sellers'));
    }

    public static function exportProvider(): array
    {
        $out = [];
        foreach (['products-best-sellers', 'payment-methods', 'users-performance'] as $report) {
            $out["$report excel"] = [$report, 'export', 'spreadsheetml'];
            $out["$report csv"] = [$report, 'export-csv', 'text/csv'];
            $out["$report pdf"] = [$report, 'export-pdf', 'application/pdf'];
        }

        return $out;
    }

    #[DataProvider('exportProvider')]
    public function test_exports_download_file(string $report, string $action, string $contentType)
    {
        $admin = $this->createAdminUser();
        $user = User::factory()->create(['full_name' => 'Ana <b>Test</b>']);
        $transaction = Transaction::factory()->create(['user_id' => $user->id, 'occurred_at' => now()->subDay()]);
        TransactionItem::factory()->create(['transaction_id' => $transaction->id]);
        TransactionPayment::factory()->create(['transaction_id' => $transaction->id, 'created_at' => now()->subDay()]);

        $response = $this->actingAs($admin, 'admin')->post("/admin/reports/$report/$action", [
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $response->assertOk();
        $this->assertStringContainsString($contentType, $response->headers->get('Content-Type'));
        $this->assertNotEmpty($response->streamedContent());
    }

    public function test_export_validates_dates()
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->postJson('/admin/reports/payment-methods/export-pdf', [])
            ->assertStatus(422);
    }
}
