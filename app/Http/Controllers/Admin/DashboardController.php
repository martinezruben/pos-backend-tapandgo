<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\License;
use App\Models\Location;
use App\Services\DashboardService;
use App\Support\AdminRbac;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        // Es la página de inicio tras el login: sin permiso se muestra una
        // bienvenida sin cifras en lugar de un 403.
        if (! auth('admin')->user()?->can(AdminRbac::permissionsForScreen('dashboard')['view'])) {
            return view('admin.dashboard-welcome');
        }

        $locationId = $request->query('location_id');
        $service = new DashboardService($locationId);

        $kpisData = $service->getKpis();
        $salesToday = $kpisData['salesToday'];
        $txToday = $kpisData['txToday'];
        $salesYesterday = $kpisData['salesYesterday'];
        $txYesterday = $kpisData['txYesterday'];
        $avgTicketToday = $kpisData['avgTicketToday'];
        $avgTicketYesterday = $kpisData['avgTicketYesterday'];
        $sales7d = $kpisData['sales7d'];
        $sales30d = $kpisData['sales30d'];
        $salesThisWeek = $kpisData['salesThisWeek'];
        $salesLastWeek = $kpisData['salesLastWeek'];
        $weekDeltaPct = $kpisData['weekDeltaPct'];
        $syncSuccess7d = $kpisData['syncSuccess7d'];
        $syncFailed7d = $kpisData['syncFailed7d'];
        $syncOkPct = $kpisData['syncOkPct'];

        $start14 = now()->subDays(13)->startOfDay();
        $start30 = now()->subDays(29)->startOfDay();

        $kpis = [
            [
                'label' => 'Localidades activas',
                'value' => (string) Location::query()->where('is_active', true)->count(),
                'icon' => 'map-pin',
                'accent' => 'from-primary-500 to-primary-600',
            ],
            [
                'label' => 'Dispositivos',
                'value' => (string) Device::query()->where('is_enabled', true)->count(),
                'icon' => 'device-phone-mobile',
                'accent' => 'from-sky-500 to-cyan-500',
            ],
            [
                'label' => 'Ventas hoy',
                'value' => '$'.number_format($salesToday, 2),
                'icon' => 'banknotes',
                'accent' => 'from-emerald-500 to-teal-600',
            ],
            [
                'label' => 'Transacciones hoy',
                'value' => (string) $txToday,
                'icon' => 'queue-list',
                'accent' => 'from-violet-500 to-purple-600',
            ],
            [
                'label' => 'Ticket promedio hoy',
                'value' => $txToday > 0 ? '$'.number_format($avgTicketToday, 2) : '—',
                'sub' => $this->deltaLabel($avgTicketToday, $avgTicketYesterday),
                'icon' => 'credit-card',
                'accent' => 'from-fuchsia-500 to-pink-600',
            ],
            [
                'label' => 'Ventas semana vs. anterior',
                'value' => $weekDeltaPct === null ? '—' : ($weekDeltaPct >= 0 ? '+' : '').number_format($weekDeltaPct, 1).'%',
                'sub' => '$'.number_format($salesThisWeek, 2).' vs $'.number_format($salesLastWeek, 2),
                'icon' => $weekDeltaPct !== null && $weekDeltaPct < 0 ? 'arrow-trending-down' : 'arrow-trending-up',
                'accent' => $weekDeltaPct !== null && $weekDeltaPct < 0 ? 'from-rose-500 to-red-600' : 'from-teal-500 to-emerald-600',
            ],
            [
                'label' => 'Ventas (7 días)',
                'value' => '$'.number_format($sales7d, 2),
                'icon' => 'chart-bar',
                'accent' => 'from-primary-600 to-sky-500',
            ],
            [
                'label' => 'Licencias activas',
                'value' => (string) License::query()->where('status', 'ACTIVE')->count(),
                'icon' => 'key',
                'accent' => 'from-amber-500 to-orange-500',
            ],
        ];

        // Datos para gráficos
        $salesTrend = $service->getDailySalesTrend($start30, now()->endOfDay());
        $familyMix = $service->getSalesByFamily(30);
        $syncByDay = $service->getSyncSuccessFailedByDay($start14, now()->endOfDay());
        $topLocations = $service->getTopLocationsBySales(30, 5);
        $activity = $service->getRecentActivity(8);
        $topProducts = $service->getTopProductsBySales(30, 5);
        $paymentMix = $service->getSalesByPaymentMethod(30);

        // Alertas
        $alerts = [
            'contingencies' => $service->getLocationsInContingency(),
            'devicesNoSync' => $service->getDevicesNotSyncedSince(4),
            'licensesExpiring' => $service->getLicensesExpiringWithin(7),
            'syncFailures24h' => $service->getSyncFailuresLast24h(),
        ];
        $voided = $service->getVoidedTransactions();

        $chartPayload = [
            'salesTrend' => $salesTrend,
            'familyMix' => $familyMix,
            'syncByDay' => $syncByDay,
            'paymentMix' => $paymentMix,
            'summary' => [
                'sales30d' => round($sales30d, 2),
                'syncOkPct' => $syncOkPct,
                'syncSuccess7d' => $syncSuccess7d,
                'syncFailed7d' => $syncFailed7d,
            ],
        ];

        return view('admin.dashboard', [
            'kpis' => $kpis,
            'chartPayload' => $chartPayload,
            'topLocations' => $topLocations,
            'topProducts' => $topProducts,
            'activity' => $activity,
            'alerts' => $alerts,
            'voided' => $voided,
            'locationId' => $locationId,
        ]);
    }

    /** Etiqueta de variación % vs. el día anterior. */
    private function deltaLabel(float $current, float $previous): string
    {
        if ($previous <= 0) {
            return $current > 0 ? 'vs. ayer: nuevo' : 'vs. ayer: —';
        }
        $pct = round(100 * ($current - $previous) / $previous, 1);

        return 'vs. ayer: '.($pct >= 0 ? '+' : '').$pct.'%';
    }
}
