<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\DashboardService;
use App\Support\AdminRbac;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Dos dashboards: comercial (ventas, `dashboard.view`) y técnico
 * (operación de localidades, dispositivos y sync, `dashboard_technical.view`).
 */
class DashboardController extends Controller
{
    public function commercial(Request $request): View|RedirectResponse
    {
        // Es la página de inicio tras el login: sin permiso se lleva al técnico
        // o a una bienvenida sin cifras en lugar de un 403.
        if (! $this->canView('dashboard')) {
            return $this->canView('dashboard-technical')
                ? redirect()->route('admin.dashboard.technical')
                : view('admin.dashboard-welcome');
        }

        $location = $this->selectedLocation($request);
        $service = new DashboardService($location?->id);

        $k = $service->getKpis();
        $voided = $service->getVoidedTransactions();

        $kpis = [
            [
                'label' => 'Ventas hoy',
                'value' => '$'.number_format($k['salesToday'], 2),
                'icon' => 'banknotes',
            ],
            [
                'label' => 'Transacciones hoy',
                'value' => (string) $k['txToday'],
                'icon' => 'queue-list',
            ],
            [
                'label' => 'Ticket promedio hoy',
                'value' => $k['txToday'] > 0 ? '$'.number_format($k['avgTicketToday'], 2) : '—',
                'sub' => $this->deltaLabel($k['avgTicketToday'], $k['avgTicketYesterday']),
                'icon' => 'credit-card',
            ],
            [
                'label' => 'Ventas semana vs. anterior',
                'value' => $k['weekDeltaPct'] === null ? '—' : ($k['weekDeltaPct'] >= 0 ? '+' : '').number_format($k['weekDeltaPct'], 1).'%',
                'sub' => '$'.number_format($k['salesThisWeek'], 2).' vs $'.number_format($k['salesLastWeek'], 2),
                'icon' => $k['weekDeltaPct'] !== null && $k['weekDeltaPct'] < 0 ? 'arrow-trending-down' : 'arrow-trending-up',
            ],
            [
                'label' => 'Ventas (7 días)',
                'value' => '$'.number_format($k['sales7d'], 2),
                'icon' => 'chart-bar',
            ],
            [
                'label' => 'Ventas (30 días)',
                'value' => '$'.number_format($k['sales30d'], 2),
                'icon' => 'chart-bar',
            ],
            [
                'label' => 'Anulaciones hoy',
                'value' => (string) $voided['today']['count'],
                'sub' => '$'.number_format($voided['today']['total'], 2),
                'icon' => 'x-circle',
                'tone' => $voided['today']['count'] > 0 ? 'warn' : null,
            ],
            [
                'label' => 'Anulaciones (7 días)',
                'value' => (string) $voided['week']['count'],
                'sub' => '$'.number_format($voided['week']['total'], 2),
                'icon' => 'x-circle',
            ],
        ];

        $chartPayload = [
            'salesTrend' => $service->getDailySalesTrend(now()->subDays(29)->startOfDay(), now()->endOfDay()),
            'familyMix' => $service->getSalesByFamily(30),
            'paymentMix' => $service->getSalesByPaymentMethod(30),
        ];

        return view('admin.dashboard', [
            'kpis' => $kpis,
            'chartPayload' => $chartPayload,
            // Con una localidad elegida el ranking de localidades no aporta
            'topLocations' => $location ? null : $service->getTopLocationsBySales(30, 5),
            'topProducts' => $service->getTopProductsBySales(30, 5),
            'locationOptions' => $this->locationOptions(),
            'selectedLocation' => $location,
        ]);
    }

    public function technical(Request $request): View|RedirectResponse
    {
        if (! $this->canView('dashboard-technical')) {
            return redirect()->route('admin.dashboard');
        }

        $location = $this->selectedLocation($request);
        $service = new DashboardService($location?->id);

        $contingencies = $service->getLocationsInContingency();
        $devicesNoSync = $service->getDevicesNotSyncedSince(4);
        $licensesExpiring = $service->getLicensesExpiringWithin(7);
        $sync7d = $service->getSyncStats7d();
        $syncFailures24h = $service->getSyncFailuresLast24h();
        $counts = $service->getInventoryCounts();

        $kpis = [
            [
                'label' => 'Localidades activas',
                'value' => (string) $counts['locations'],
                'icon' => 'map-pin',
            ],
            [
                'label' => 'En contingencia',
                'value' => (string) count($contingencies),
                'sub' => count($contingencies) > 0 ? 'Ver localidades' : 'Ninguna',
                'icon' => 'exclamation-triangle',
                'tone' => count($contingencies) > 0 ? 'warn' : null,
                'modal' => count($contingencies) > 0 ? 'dash-contingencies' : null,
            ],
            [
                'label' => 'Dispositivos habilitados',
                'value' => (string) $counts['devices'],
                'icon' => 'device-phone-mobile',
            ],
            [
                'label' => 'Sin sincronizar (+4 h)',
                'value' => (string) count($devicesNoSync),
                'sub' => count($devicesNoSync) > 0 ? 'Ver dispositivos' : 'Todos al día',
                'icon' => 'arrow-path',
                'tone' => count($devicesNoSync) > 0 ? 'warn' : null,
                'modal' => count($devicesNoSync) > 0 ? 'dash-devices-no-sync' : null,
            ],
            [
                'label' => 'Licencias activas',
                'value' => (string) $counts['licenses'],
                'icon' => 'key',
            ],
            [
                'label' => 'Licencias por vencer (7 días)',
                'value' => (string) count($licensesExpiring),
                'sub' => count($licensesExpiring) > 0 ? 'Ver licencias' : 'Ninguna',
                'icon' => 'key',
                'tone' => count($licensesExpiring) > 0 ? 'warn' : null,
                'modal' => count($licensesExpiring) > 0 ? 'dash-licenses-expiring' : null,
            ],
            [
                'label' => 'Sync correctas (7 días)',
                'value' => $sync7d['okPct'] === null ? '—' : $sync7d['okPct'].'%',
                'sub' => $sync7d['success'].' de '.($sync7d['success'] + $sync7d['failed']),
                'icon' => 'arrow-path',
            ],
            [
                'label' => 'Sync fallidas (24 h)',
                'value' => (string) $syncFailures24h['count'],
                'sub' => 'de '.$syncFailures24h['total'].' sincronizaciones',
                'icon' => 'x-circle',
                'tone' => $syncFailures24h['count'] > 0 ? 'warn' : null,
            ],
        ];

        return view('admin.dashboard-technical', [
            'kpis' => $kpis,
            'contingencies' => $contingencies,
            'devicesNoSync' => $devicesNoSync,
            'licensesExpiring' => $licensesExpiring,
            'activity' => $service->getRecentActivity(8),
            'chartPayload' => [
                'syncByDay' => $service->getSyncSuccessFailedByDay(now()->subDays(13)->startOfDay(), now()->endOfDay()),
            ],
            'locationOptions' => $this->locationOptions(),
            'selectedLocation' => $location,
        ]);
    }

    /** Localidad del filtro `?location_id=`; un ID desconocido se ignora. */
    private function selectedLocation(Request $request): ?Location
    {
        $id = $request->query('location_id');

        return is_string($id) && $id !== '' ? Location::query()->find($id) : null;
    }

    /**
     * @return Collection<int, Location>
     */
    private function locationOptions()
    {
        return Location::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    private function canView(string $screen): bool
    {
        return (bool) auth('admin')->user()?->can(AdminRbac::permissionsForScreen($screen)['view']);
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
