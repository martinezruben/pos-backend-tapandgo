<?php

namespace App\Services;

use App\Models\Device;
use App\Models\License;
use App\Models\Location;
use App\Models\PaymentMethod;
use App\Models\SyncLog;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private ?string $locationId = null;

    public function __construct(?string $locationId = null)
    {
        $this->locationId = $locationId;
    }

    public function getKpis(): array
    {
        $today = now()->toDateString();
        $start30 = now()->subDays(29)->startOfDay();
        $start7 = now()->subDays(6)->startOfDay();
        $start14 = now()->subDays(13)->startOfDay();
        $weekStart = now()->startOfWeek();

        // Todas las sumas de hoy, ayer, 7d, 30d en una sola consulta
        $summary = Transaction::query()
            ->where('status', 'PAID')
            ->selectRaw('
                SUM(CASE WHEN DATE(occurred_at) = ? THEN total ELSE 0 END) as sales_today,
                COUNT(CASE WHEN DATE(occurred_at) = ? THEN 1 ELSE NULL END) as tx_today,
                SUM(CASE WHEN DATE(occurred_at) = ? THEN total ELSE 0 END) as sales_yesterday,
                COUNT(CASE WHEN DATE(occurred_at) = ? THEN 1 ELSE NULL END) as tx_yesterday,
                SUM(CASE WHEN occurred_at >= ? THEN total ELSE 0 END) as sales_7d,
                SUM(CASE WHEN occurred_at >= ? THEN total ELSE 0 END) as sales_30d,
                SUM(CASE WHEN occurred_at >= ? AND occurred_at < ? THEN total ELSE 0 END) as sales_this_week,
                SUM(CASE WHEN occurred_at >= ? AND occurred_at < ? THEN total ELSE 0 END) as sales_last_week
            ', [
                $today, $today,
                now()->subDay()->toDateString(), now()->subDay()->toDateString(),
                $start7, $start30,
                $weekStart, now()->addSecond(),
                $weekStart->copy()->subDays(7), $weekStart->copy()->subSecond(),
            ])
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->first();

        $salesToday = (float) ($summary->sales_today ?? 0);
        $txToday = (int) ($summary->tx_today ?? 0);
        $salesYesterday = (float) ($summary->sales_yesterday ?? 0);
        $txYesterday = (int) ($summary->tx_yesterday ?? 0);
        $sales7d = (float) ($summary->sales_7d ?? 0);
        $sales30d = (float) ($summary->sales_30d ?? 0);
        $salesThisWeek = (float) ($summary->sales_this_week ?? 0);
        $salesLastWeek = (float) ($summary->sales_last_week ?? 0);

        $avgTicketToday = $txToday > 0 ? $salesToday / $txToday : 0.0;
        $avgTicketYesterday = $txYesterday > 0 ? $salesYesterday / $txYesterday : 0.0;

        $weekDeltaPct = $salesLastWeek > 0
            ? round(100 * ($salesThisWeek - $salesLastWeek) / $salesLastWeek, 1)
            : ($salesThisWeek > 0 ? 100.0 : null);

        // Sincronizaciones
        $syncLogs7d = SyncLog::query()
            ->where('started_at', '>=', now()->subDays(7))
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $syncSuccess7d = (int) ($syncLogs7d['SUCCESS'] ?? 0);
        $syncFailed7d = (int) ($syncLogs7d['FAILED'] ?? 0);
        $syncTotal7d = $syncSuccess7d + $syncFailed7d;
        $syncOkPct = $syncTotal7d > 0 ? round(100 * $syncSuccess7d / $syncTotal7d, 1) : null;

        return [
            'salesToday' => $salesToday,
            'txToday' => $txToday,
            'salesYesterday' => $salesYesterday,
            'txYesterday' => $txYesterday,
            'avgTicketToday' => $avgTicketToday,
            'avgTicketYesterday' => $avgTicketYesterday,
            'sales7d' => $sales7d,
            'sales30d' => $sales30d,
            'salesThisWeek' => $salesThisWeek,
            'salesLastWeek' => $salesLastWeek,
            'weekDeltaPct' => $weekDeltaPct,
            'syncSuccess7d' => $syncSuccess7d,
            'syncFailed7d' => $syncFailed7d,
            'syncOkPct' => $syncOkPct,
        ];
    }

    public function getDailySalesTrend(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $dateSql = $this->sqlDateColumn('occurred_at');
        $rows = Transaction::query()
            ->where('status', 'PAID')
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw("{$dateSql} as d, SUM(total) as sales, COUNT(*) as cnt")
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
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

    public function getSalesByFamily(int $days): array
    {
        $since = now()->subDays($days)->startOfDay();
        $top = 5;

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->leftJoin('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('subfamilies', 'products.subfamily_id', '=', 'subfamilies.id')
            ->leftJoin('families', 'subfamilies.family_id', '=', 'families.id')
            ->where('transactions.status', 'PAID')
            ->where('transactions.occurred_at', '>=', $since)
            ->when($this->locationId, fn ($q) => $q->where('transactions.location_id', $this->locationId))
            ->selectRaw('families.id as id, MAX(families.name) as name, SUM(transaction_items.line_total) as total')
            ->groupBy('families.id')
            ->get();

        $unassigned = (float) $rows->whereNull('id')->sum('total');
        $families = $rows->whereNotNull('id')
            ->sort(fn ($a, $b) => [(float) $b->total, (string) $a->name] <=> [(float) $a->total, (string) $b->name])
            ->values();

        $labels = [];
        $series = [];
        foreach ($families->take($top) as $row) {
            $labels[] = (string) $row->name;
            $series[] = (float) $row->total;
        }

        $others = (float) $families->slice($top)->sum('total');
        if ($others > 0) {
            $labels[] = 'Otros';
            $series[] = $others;
        }
        if ($unassigned > 0) {
            $labels[] = 'Sin familia';
            $series[] = $unassigned;
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function getSyncSuccessFailedByDay(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $dateSql = $this->sqlDateColumn('started_at');
        $raw = SyncLog::query()
            ->whereBetween('started_at', [$from, $to])
            ->selectRaw("{$dateSql} as d, status, COUNT(*) as c")
            ->groupBy(DB::raw($dateSql), 'status')
            ->get();

        $byDay = [];
        foreach ($raw as $row) {
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

    public function getSalesByPaymentMethod(int $days): array
    {
        $since = now()->subDays($days)->startOfDay();

        $rows = Transaction::query()
            ->where('status', 'PAID')
            ->where('occurred_at', '>=', $since)
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->join('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->selectRaw('transaction_payments.payment_method, SUM(transaction_payments.amount) as total')
            ->groupBy('transaction_payments.payment_method')
            ->orderByDesc('total')
            ->get();

        $methodLabels = PaymentMethod::labelsFor($rows->pluck('payment_method'));

        return [
            'labels' => $rows->pluck('payment_method')->map(fn ($m) => $methodLabels[$m] ?? $m)->values()->all(),
            'series' => $rows->pluck('total')->map(fn ($v) => round((float) $v, 2))->values()->all(),
        ];
    }

    public function getTopProductsBySales(int $days, int $limit): array
    {
        $since = now()->subDays($days)->startOfDay();

        $rows = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->leftJoin('products', 'transaction_items.product_id', '=', 'products.id')
            ->where('transactions.status', 'PAID')
            ->where('transactions.occurred_at', '>=', $since)
            ->whereNotNull('transaction_items.product_id')
            ->when($this->locationId, fn ($q) => $q->where('transactions.location_id', $this->locationId))
            ->selectRaw("transaction_items.product_id, MAX(COALESCE(NULLIF(products.name, ''), NULLIF(transaction_items.product_name, ''))) as name, SUM(transaction_items.qty) as qty, SUM(transaction_items.line_total) as total")
            ->groupBy('transaction_items.product_id')
            ->orderByDesc('total')
            ->limit($limit * 2)
            ->get();

        $rows = $rows->filter(fn ($row) => $row->name !== null && trim((string) $row->name) !== '')->take($limit);

        if ($rows->isEmpty()) {
            return [];
        }

        // Participación sobre todo lo vendido en el periodo, no sobre el top
        $periodTotal = (float) DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'PAID')
            ->where('transactions.occurred_at', '>=', $since)
            ->when($this->locationId, fn ($q) => $q->where('transactions.location_id', $this->locationId))
            ->sum('transaction_items.line_total');

        return $rows
            ->map(fn ($row): array => [
                'name' => (string) $row->name,
                'qty' => (float) $row->qty,
                'total' => (float) $row->total,
                'pct' => $periodTotal > 0 ? round(100 * (float) $row->total / $periodTotal, 1) : 0.0,
            ])
            ->all();
    }

    public function getTopLocationsBySales(int $days, int $limit): array
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

        // Participación sobre todas las ventas del periodo, no sobre el top
        $periodTotal = (float) Transaction::query()
            ->where('status', 'PAID')
            ->where('occurred_at', '>=', $since)
            ->sum('total');

        $locationIds = $totals->pluck('location_id')->all();
        $names = Location::query()->whereIn('id', $locationIds)->pluck('name', 'id');

        $out = [];
        foreach ($totals as $row) {
            $t = (float) $row->total;
            $out[] = [
                'name' => (string) ($names[$row->location_id] ?? '—'),
                'total' => $t,
                'pct' => $periodTotal > 0 ? round(100 * $t / $periodTotal, 1) : 0.0,
            ];
        }

        return $out;
    }

    public function getRecentActivity(int $limit): array
    {
        $sync = SyncLog::query()
            ->with(['location:id,name', 'device:id,name,device_fingerprint'])
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->orderByDesc('started_at')
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

    public function getLocationsInContingency(): array
    {
        return Location::query()
            ->where('is_active', true)
            ->whereNotNull('contingency_started_at')
            ->orderBy('contingency_started_at')
            ->get(['name', 'contingency_started_at'])
            ->map(fn ($loc) => [
                'name' => $loc->name,
                'since' => $loc->contingency_started_at->copy()->locale('es')->diffForHumans(),
            ])
            ->all();
    }

    public function getDevicesNotSyncedSince(int $hours = 4): array
    {
        $since = now()->subHours($hours);

        return Device::query()
            ->where('is_enabled', true)
            ->where(function ($q) use ($since) {
                $q->whereNull('last_sync_at')->orWhere('last_sync_at', '<', $since);
            })
            ->with('location:id,name')
            ->orderByRaw('COALESCE(last_sync_at, created_at) ASC')
            ->get(['id', 'name', 'location_id', 'last_sync_at', 'created_at'])
            ->map(function ($dev) {
                $last = $dev->last_sync_at ?? $dev->created_at;

                return [
                    'name' => $dev->name ?: $dev->id,
                    'location' => $dev->location?->name ?? '—',
                    'hours_ago' => (int) ceil($last->diffInMinutes(now()) / 60),
                ];
            })
            ->all();
    }

    public function getLicensesExpiringWithin(int $days = 7): array
    {
        $until = now()->addDays($days)->endOfDay();

        return License::query()
            ->where('status', 'ACTIVE')
            ->whereBetween('valid_to', [now(), $until])
            ->with(['device:id,name,location_id', 'device.location:id,name'])
            ->orderBy('valid_to')
            ->get()
            ->map(fn ($lic) => [
                'device' => $lic->device?->name ?: $lic->device_id,
                'location' => $lic->device?->location?->name ?? '—',
                'expires_in' => $lic->valid_to->copy()->locale('es')->diffForHumans(),
            ])
            ->all();
    }

    public function getSyncFailuresLast24h(): array
    {
        $since = now()->subDay();

        $result = SyncLog::query()
            ->where('started_at', '>=', $since)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "FAILED" THEN 1 ELSE 0 END) as failed')
            ->first();

        return [
            'count' => (int) ($result->failed ?? 0),
            'total' => (int) ($result->total ?? 0),
        ];
    }

    public function getVoidedTransactions(): array
    {
        $today = now()->toDateString();
        $weekStart = now()->subDays(6)->startOfDay();

        $todayRow = Transaction::query()
            ->where('status', 'VOIDED')
            ->whereDate('occurred_at', $today)
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        $weekRow = Transaction::query()
            ->where('status', 'VOIDED')
            ->where('occurred_at', '>=', $weekStart)
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total')
            ->first();

        return [
            'today' => [
                'count' => (int) ($todayRow->count ?? 0),
                'total' => (float) ($todayRow->total ?? 0),
            ],
            'week' => [
                'count' => (int) ($weekRow->count ?? 0),
                'total' => (float) ($weekRow->total ?? 0),
            ],
        ];
    }

    private function sqlDateColumn(string $column): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            default => "DATE({$column})",
        };
    }
}
