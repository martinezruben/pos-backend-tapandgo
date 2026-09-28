<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Services\DashboardService;
use App\Support\AdminRbac;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Dos dashboards: comercial (ventas, `dashboard.view`) y técnico
 * (operación de localidades, dispositivos y sync, `dashboard_technical.view`).
 */
class DashboardController extends Controller
{
    /** Segundos que se reutilizan las cifras de cada dashboard. */
    private const CACHE_SECONDS = 60;

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
        $data = $this->cached('commercial', $location, fn () => $this->commercialData($location));

        return view('admin.dashboard', $data + [
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
        $data = $this->cached('technical', $location, fn () => $this->technicalData($location));

        return view('admin.dashboard-technical', $data + [
            'locationOptions' => $this->locationOptions(),
            'selectedLocation' => $location,
        ]);
    }

    /**
     * @return array{kpis: list<array<string, mixed>>, chartPayload: array<string, mixed>, topLocations: ?array, topProducts: array}
     */
    private function commercialData(?Location $location): array
    {
        $service = new DashboardService($location?->id);

        $k = $service->getKpis();
        $voided = $service->getVoidedTransactions();

        $kpis = [
            [
                'label' => 'Ventas hoy',
                'value' => Format::money($k['salesToday']),
                'icon' => 'banknotes',
            ],
            [
                'label' => 'Transacciones hoy',
                'value' => (string) $k['txToday'],
                'icon' => 'queue-list',
            ],
            [
                'label' => 'Ticket promedio hoy',
                'value' => $k['txToday'] > 0 ? Format::money($k['avgTicketToday']) : '—',
                'sub' => $this->deltaLabel($k['avgTicketToday'], $k['avgTicketYesterday']),
                'icon' => 'credit-card',
            ],
            [
                'label' => 'Ventas semana vs. anterior',
                'value' => $k['weekDeltaPct'] === null ? '—' : ($k['weekDeltaPct'] >= 0 ? '+' : '').Format::percent($k['weekDeltaPct']),
                'sub' => Format::money($k['salesThisWeek']).' vs '.Format::money($k['salesLastWeek']),
                'icon' => $k['weekDeltaPct'] !== null && $k['weekDeltaPct'] < 0 ? 'arrow-trending-down' : 'arrow-trending-up',
            ],
            [
                'label' => 'Ventas (7 días)',
                'value' => Format::money($k['sales7d']),
                'icon' => 'chart-bar',
            ],
            [
                'label' => 'Ventas (30 días)',
                'value' => Format::money($k['sales30d']),
                'icon' => 'chart-bar',
            ],
            [
                'label' => 'Anulaciones hoy',
                'value' => (string) $voided['today']['count'],
                'sub' => Format::money($voided['today']['total']),
                'icon' => 'x-circle',
                'tone' => $voided['today']['count'] > 0 ? 'warn' : null,
            ],
            [
                'label' => 'Anulaciones (7 días)',
                'value' => (string) $voided['week']['count'],
                'sub' => Format::money($voided['week']['total']),
                'icon' => 'x-circle',
            ],
        ];

        $chartPayload = [
            'salesTrend' => $service->getDailySalesTrend(now()->subDays(29)->startOfDay(), now()->endOfDay()),
            'familyMix' => $service->getSalesByFamily(30),
            'paymentMix' => $service->getSalesByPaymentMethod(30),
        ];

        return [
            'kpis' => $kpis,
            'chartPayload' => $chartPayload,
            // Con una localidad elegida el ranking de localidades no aporta
            'topLocations' => $location ? null : $service->getTopLocationsBySales(30, 5),
            'topProducts' => $service->getTopProductsBySales(30, 5),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function technicalData(?Location $location): array
    {
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

        return [
            'kpis' => $kpis,
            'contingencies' => $contingencies,
            'devicesNoSync' => $devicesNoSync,
            'licensesExpiring' => $licensesExpiring,
            'activity' => $service->getRecentActivity(8),
            'chartPayload' => [
                'syncByDay' => $service->getSyncSuccessFailedByDay(now()->subDays(13)->startOfDay(), now()->endOfDay()),
            ],
        ];
    }

    /**
     * Cifras del dashboard en caché por dashboard y localidad; `generatedAt`
     * indica en pantalla a qué hora se calcularon.
     *
     * @param  callable(): array<string, mixed>  $build
     * @return array<string, mixed>
     */
    private function cached(string $dashboard, ?Location $location, callable $build): array
    {
        $key = 'dashboard:'.$dashboard.':'.($location?->id ?? 'all');

        return Cache::remember($key, self::CACHE_SECONDS, fn () => $build() + ['generatedAt' => now()->format('H:i')]);
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
