<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\Location;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Subfamily;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithDashboard(): AdminUser
    {
        Permission::findOrCreate('dashboard.view', 'admin');
        $admin = AdminUser::factory()->create();
        $admin->givePermissionTo('dashboard.view');

        return $admin;
    }

    private function paidTransaction(Location $location, float $total): Transaction
    {
        return Transaction::factory()->create([
            'location_id' => $location->id,
            'status' => 'PAID',
            'total' => $total,
            'occurred_at' => now()->subDay(),
        ]);
    }

    private function line(Transaction $tx, ?Product $product, float $total): void
    {
        TransactionItem::factory()->create([
            'transaction_id' => $tx->id,
            'product_id' => $product?->id,
            'product_name' => $product?->name ?? 'Suelto',
            'qty' => 1,
            'unit_price' => $total,
            'line_total' => $total,
        ]);
    }

    public function test_admin_without_permission_sees_welcome_without_figures(): void
    {
        $admin = AdminUser::factory()->create();
        $this->paidTransaction(Location::factory()->create(), 123.45);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard-welcome')
            ->assertDontSee('123.45');
    }

    public function test_admin_with_permission_sees_dashboard(): void
    {
        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewIs('admin.dashboard');
    }

    public function test_dashboard_permission_is_assignable_in_rbac_matrix(): void
    {
        $this->assertSame(['dashboard.view'], AdminRbac::managedPermissionNamesForScreen('dashboard'));
        $this->assertContains('dashboard.view', AdminRbac::allManagedPermissionNames());
        $this->assertContains('dashboard.view', AdminRbac::allCrudPermissionNames());
        $this->assertNotContains('dashboard.edit', AdminRbac::allCrudPermissionNames());
    }

    public function test_location_share_is_over_period_total_not_top(): void
    {
        // 6 localidades: el top 5 no suma el 100 % de lo vendido
        $locations = Location::factory()->count(6)->create();
        foreach ([500, 200, 100, 100, 50, 50] as $i => $total) {
            $this->paidTransaction($locations[$i], $total);
        }

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertViewHas('topLocations', function (array $top): bool {
                return count($top) === 5
                    && $top[0]['pct'] === 50.0
                    && array_sum(array_column($top, 'pct')) === 95.0;
            });
    }

    public function test_product_share_is_over_period_total_not_top(): void
    {
        $tx = $this->paidTransaction(Location::factory()->create(), 1000);
        foreach ([400, 200, 100, 100, 100, 100] as $total) {
            $this->line($tx, Product::factory()->create(), $total);
        }

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertViewHas('topProducts', fn (array $top): bool => $top[0]['pct'] === 40.0
                && array_sum(array_column($top, 'pct')) === 90.0);
    }

    public function test_family_mix_includes_others_and_unassigned(): void
    {
        $tx = $this->paidTransaction(Location::factory()->create(), 1000);
        foreach ([300, 200, 100, 100, 100, 50, 50] as $i => $total) {
            $family = Family::create(['name' => 'Fam '.$i]);
            $sub = Subfamily::create(['family_id' => $family->id, 'name' => 'Sub '.$i]);
            $this->line($tx, Product::factory()->create(['subfamily_id' => $sub->id]), $total);
        }
        $this->line($tx, Product::factory()->create(['subfamily_id' => null]), 30);
        $this->line($tx, null, 20);

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertViewHas('chartPayload', function (array $payload): bool {
                $mix = $payload['familyMix'];

                return $mix['labels'] === ['Fam 0', 'Fam 1', 'Fam 2', 'Fam 3', 'Fam 4', 'Otros', 'Sin familia']
                    && $mix['series'][5] === 100.0
                    && $mix['series'][6] === 50.0
                    && array_sum($mix['series']) === 950.0;
            });
    }

    public function test_payment_mix_uses_payment_method_names(): void
    {
        $custom = PaymentMethod::create(['name' => 'Bono regalo', 'type' => 'OTHER']);
        $tx = $this->paidTransaction(Location::factory()->create(), 100);
        TransactionPayment::factory()->create(['transaction_id' => $tx->id, 'payment_method' => $custom->id, 'amount' => 60]);
        TransactionPayment::factory()->create(['transaction_id' => $tx->id, 'payment_method' => 'CASH', 'amount' => 40]);

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertViewHas('chartPayload', fn (array $payload): bool => $payload['paymentMix']['labels'] === ['Bono regalo', 'Efectivo']);
    }

    public function test_alert_card_lists_each_operational_issue(): void
    {
        $location = Location::factory()->create(['name' => 'Sucursal Sur', 'contingency_started_at' => now()->subHours(3)]);
        $device = Device::factory()->create([
            'location_id' => $location->id,
            'name' => 'Caja Atrasada',
            'is_enabled' => true,
            'last_sync_at' => now()->subHours(6),
        ]);
        $licensed = Device::factory()->create(['location_id' => $location->id, 'name' => 'Caja Por Vencer', 'last_sync_at' => now()]);
        License::factory()->create(['device_id' => $licensed->id, 'valid_to' => now()->addDays(3)]);
        SyncLog::factory()->create(['status' => 'FAILED', 'started_at' => now()->subHour()]);
        Transaction::factory()->create(['status' => 'VOIDED', 'total' => 123.45, 'occurred_at' => now()]);

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Alertas operativas')
            ->assertSee('1 localidad(es) en contingencia')
            ->assertSee('Sucursal Sur')
            ->assertSee('dispositivo(s) sin sincronizar')
            ->assertSee('Caja Atrasada')
            ->assertSee('1 licencia(s) próxima(s) a vencer')
            ->assertSee('Caja Por Vencer')
            ->assertSee('1 sincronización(es) fallida(s)')
            ->assertSee('1 transacción(es) anulada(s) hoy')
            ->assertSee('$123.45');
    }

    public function test_alert_card_is_hidden_when_nothing_is_wrong(): void
    {
        $location = Location::factory()->create(['contingency_started_at' => null]);
        $device = Device::factory()->create(['location_id' => $location->id, 'is_enabled' => true, 'last_sync_at' => now()->subHour()]);
        License::factory()->create(['device_id' => $device->id, 'valid_to' => now()->addDays(30)]);
        SyncLog::factory()->create([
            'location_id' => $location->id,
            'device_id' => $device->id,
            'status' => 'SUCCESS',
            'started_at' => now()->subHour(),
        ]);

        $this->actingAs($this->adminWithDashboard(), 'admin')
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Alertas operativas');
    }
}
