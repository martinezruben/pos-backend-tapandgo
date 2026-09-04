<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiRequestLog;
use App\Models\Device;
use App\Models\License;
use App\Models\Location;
use App\Models\SyncLog;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->toDateString();
        $start30 = now()->subDays(29)->startOfDay();
        $start7 = now()->subDays(6)->startOfDay();
        $start14 = now()->subDays(13)->startOfDay();

        $salesToday = (float) Transaction::query()
            ->where('status', 'PAID')
            ->whereDate('occurred_at', $today)
            ->sum('total');

        $txToday = (int) Transaction::query()
            ->where('status', 'PAID')
            ->whereDate('occurred_at', $today)
            ->count();

        $salesYesterday = (float) Transaction::query()
            ->where('status', 'PAID')
            ->whereDate('occurred_at', now()->subDay())
            ->sum('total');

        $txYesterday = (int) Transaction::query()
            ->where('status', 'PAID')
            ->whereDate('occurred_at', now()->subDay())
            ->count();

        $avgTicketToday = $txToday > 0 ? $salesToday / $txToday : 0.0;
        $avgTicketYesterday = $txYesterday > 0 ? $salesYesterday / $txYesterday : 0.0;

        $sales7d = (float) Transaction::query()
            ->where('status', 'PAID')
            ->where('occurred_at', '>=', $start7)
            ->sum('total');

        $sales30d = (float) Transaction::query()
            ->where('status', 'PAID')
            ->where('occurred_at', '>=', $start30)
            ->sum('total');

        $syncLogs7d = SyncLog::query()->where('started_at', '>=', now()->subDays(7))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $syncSuccess7d = (int) ($syncLogs7d['SUCCESS'] ?? 0);
        $syncFailed7d = (int) ($syncLogs7d['FAILED'] ?? 0);
        $syncTotal7d = $syncSuccess7d + $syncFailed7d;
        $syncOkPct = $syncTotal7d > 0 ? round(100 * $syncSuccess7d / $syncTotal7d, 1) : null;

        // Comparativo de ventas: semana en curso (lunes→hoy) vs. semana anterior completa
        $weekStart = now()->startOfWeek();
        $salesThisWeek = (float) Transaction::query()
            ->where('status', 'PAID')
            ->whereBetween('occurred_at', [$weekStart, now()])
            ->sum('total');
        $salesLastWeek = (float) Transaction::query()
            ->where('status', 'PAID')
            ->whereBetween('occurred_at', [$weekStart->copy()->subDays(7), $weekStart->copy()->subSecond()])
            ->sum('total');
        $weekDeltaPct = $salesLastWeek > 0
            ? round(100 * ($salesThisWeek - $salesLastWeek) / $salesLastWeek, 1)
            : ($salesThisWeek > 0 ? 100.0 : null);
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
                'icon' => 'chart-bar',
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

        $salesTrend = $this->dailySalesTrend($start30, now()->endOfDay());
        $familyMix = $this->salesByFamily(30);
        $syncByDay = $this->syncSuccessFailedByDay($start14, now()->endOfDay());
        $topLocations = $this->topLocationsBySales(30, 5);
        $activity = $this->recentActivity(8);
        $topProducts = $this->topProductsBySales(30, 5);
        $paymentMix = $this->salesByPaymentMethod(30);

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

    /**
     * @return list<array{name: string, qty: float, total: float, pct: float}>
     */
    private function topProductsBySales(int $days, int $limit): array
    {
        $since = now()->subDays($days)->startOfDay();

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'PAID')
            ->where('transactions.occurred_at', '>=', $since)
            ->whereNotNull('transaction_items.product_id')
            ->selectRaw('transaction_items.product_id, MAX(transaction_items.product_name) as name, SUM(transaction_items.qty) as qty, SUM(transaction_items.line_total) as total')
            ->groupBy('transaction_items.product_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $sumTop = (float) $rows->sum('total');

        return $rows
            ->map(fn ($row): array => [
                'name' => (string) ($row->name ?: '—'),
                'qty' => (float) $row->qty,
                'total' => (float) $row->total,
                'pct' => $sumTop > 0 ? round(100 * (float) $row->total / $sumTop, 1) : 0.0,
            ])
            ->all();
    }

    /**
     * @return array{labels: list<string>, series: list<float>}
     */
    private function salesByPaymentMethod(int $days): array
    {
        $since = now()->subDays($days)->startOfDay();

        $rows = TransactionPayment::query()
            ->whereHas('transaction', fn ($q) => $q
                ->where('status', 'PAID')
                ->where('occurred_at', '>=', $since))
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $methodLabels = ['CASH' => 'Efectivo', 'CARD' => 'Tarjeta', 'TRANSFER' => 'Transferencia', 'OTHER' => 'Otro'];

        return [
            'labels' => $rows->pluck('payment_method')->map(fn ($m) => $methodLabels[$m] ?? $m)->values()->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->values()->all(),
        ];
    }

    /**
     * @return array{labels: list<string>, sales: list<float>, transactions: list<int>}
     */
    private function dailySalesTrend(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $dateSql = $this->sqlDateColumn('occurred_at');
        $rows = Transaction::query()
            ->where('status', 'PAID')
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw("{$dateSql} as d, SUM(total) as sales, COUNT(*) as cnt")
            ->groupBy(DB::raw($dateSql))
            ->orderBy(DB::raw($dateSql))
            ->get()
            ->keyBy('d');

        $labels = [];
        $sales = [];
        $transactions = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->translatedFormat('d M');
            $row = $rows->get($key);
            $sales[] = $row ? (float) $row->sales : 0.0;
            $transactions[] = $row ? (int) $row->cnt : 0;
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'sales' => $sales,
            'transactions' => $transactions,
        ];
    }

    /**
     * @return array{labels: list<string>, series: list<float>}
     */
    private function salesByFamily(int $days): array
    {
        $since = now()->subDays($days)->startOfDay();

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('subfamilies', 'products.subfamily_id', '=', 'subfamilies.id')
            ->join('families', 'subfamilies.family_id', '=', 'families.id')
            ->where('transactions.status', 'PAID')
            ->where('transactions.occurred_at', '>=', $since)
            ->selectRaw('families.name as name, SUM(transaction_items.line_total) as total')
            ->groupBy('families.id', 'families.name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => [], 'series' => []];
        }

        $labels = $rows->pluck('name')->map(fn ($n) => (string) $n)->all();
        $series = $rows->pluck('total')->map(fn ($v) => (float) $v)->all();

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * @return array{categories: list<string>, success: list<int>, failed: list<int>}
     */
    private function syncSuccessFailedByDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $dateSql = $this->sqlDateColumn('started_at');
        $raw = SyncLog::query()
            ->whereBetween('started_at', [$from, $to])
            ->selectRaw("{$dateSql} as d, status, COUNT(*) as c")
            ->groupBy(DB::raw($dateSql), 'status')
            ->get();

        $byDay = [];
        foreach ($raw as $row) {
            // MySQL DATE() puede llegar como datetime; unificar a Y-m-d para cruzar con el bucle diario
            $d = Carbon::parse($row->d)->toDateString();
            $byDay[$d] ??= ['SUCCESS' => 0, 'FAILED' => 0];
            $byDay[$d][$row->status] = (int) $row->c;
        }

        $categories = [];
        $success = [];
        $failed = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $categories[] = $cursor->translatedFormat('d M');
            $success[] = $byDay[$key]['SUCCESS'] ?? 0;
            $failed[] = $byDay[$key]['FAILED'] ?? 0;
            $cursor->addDay();
        }

        return [
            'categories' => $categories,
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * @return list<array{name: string, total: float, pct: float}>
     */
    private function topLocationsBySales(int $days, int $limit): array
    {
        $since = now()->subDays($days)->startOfDay();

        $totals = Transaction::query()
            ->where('status', 'PAID')
            ->where('occurred_at', '>=', $since)
            ->selectRaw('location_id, SUM(total) as total')
            ->groupBy('location_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        if ($totals->isEmpty()) {
            return [];
        }

        $sumTop = (float) $totals->sum('total');
        $locationIds = $totals->pluck('location_id')->all();
        $names = Location::query()->whereIn('id', $locationIds)->pluck('name', 'id');

        $out = [];
        foreach ($totals as $row) {
            $t = (float) $row->total;
            $out[] = [
                'name' => (string) ($names[$row->location_id] ?? '—'),
                'total' => $t,
                'pct' => $sumTop > 0 ? round(100 * $t / $sumTop, 1) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * Actividad reciente: más nuevo primero. `direction` = Pull/Push de datos (sync) o inferido desde la ruta (API).
     *
     * @return list<array{location: string, device: string, direction: string, time_human: string, tone: string}>
     */
    private function recentActivity(int $limit): array
    {
        $sync = SyncLog::query()
            ->with(['location:id,name', 'device:id,name,device_fingerprint'])
            ->orderByDesc('started_at')
            ->limit($limit * 2)
            ->get();

        $api = ApiRequestLog::query()
            ->with(['location:id,name', 'device:id,name,device_fingerprint'])
            ->orderByDesc('created_at')
            ->limit($limit * 2)
            ->get();

        $merged = collect();

        foreach ($sync as $log) {
            $at = $log->started_at;
            $merged->push([
                'sort' => $at ? $at->getTimestamp() : 0,
                'location' => $log->location?->name ?? '—',
                'device' => $log->device?->name ?: ($log->device?->device_fingerprint ?? '—'),
                'direction' => $log->operation === 'PUSH' ? 'Push' : 'Pull',
                'time_human' => $at ? $at->copy()->locale('es')->diffForHumans() : '—',
                'tone' => $log->status === 'SUCCESS' ? 'emerald' : 'rose',
            ]);
        }

        foreach ($api as $row) {
            $at = $row->created_at;
            $loc = $row->location?->name ?? '—';
            $dev = $row->device?->name ?: ($row->device?->device_fingerprint ?? $row->device_fingerprint ?? '—');
            $merged->push([
                'sort' => $at ? $at->getTimestamp() : 0,
                'location' => $loc,
                'device' => $dev,
                'direction' => $this->apiSyncDirectionLabel($row->path),
                'time_human' => $at ? $at->copy()->locale('es')->diffForHumans() : '—',
                'tone' => $row->response_status >= 200 && $row->response_status < 400 ? 'sky' : 'amber',
            ]);
        }

        return $merged->sortByDesc('sort')
            ->take($limit)
            ->map(fn (array $e) => [
                'location' => $e['location'],
                'device' => $e['device'],
                'direction' => $e['direction'],
                'time_human' => $e['time_human'],
                'tone' => $e['tone'],
            ])
            ->values()
            ->all();
    }

    /** Pull/Push según ruta; si no es sync, etiqueta corta. */
    private function apiSyncDirectionLabel(string $path): string
    {
        $p = strtolower($path);

        if (str_contains($p, 'sync/pull') || str_ends_with($p, '/pull')) {
            return 'Pull';
        }

        if (str_contains($p, 'sync/push') || str_ends_with($p, '/push')) {
            return 'Push';
        }

        return 'API';
    }

    private function sqlDateColumn(string $column): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            default => "DATE({$column})",
        };
    }
}
