<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $locationId = $request->query('location_id');

        // Filtrar el dia segun la zona horaria del comercio (occurred_at se guarda en UTC)
        $tz = config('app.pos_sales_timezone', 'America/Santo_Domingo');
        $dayStart = Carbon::now($tz)->startOfDay()->utc();
        $dayEnd = Carbon::now($tz)->endOfDay()->utc();

        $base = Transaction::query()->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($locationId) {
            $base->where('location_id', $locationId);
        }

        $sales = (clone $base)->sum('total');
        $tickets = (clone $base)->count();
        $avgTicket = $tickets > 0 ? round($sales / $tickets, 2) : 0;
        $onlineSales = (clone $base)->where('is_synced', true)->sum('total');

        $salesByMethod = TransactionPayment::query()
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->whereHas('transaction', function ($q) use ($locationId, $dayStart, $dayEnd): void {
                $q->whereBetween('occurred_at', [$dayStart, $dayEnd]);
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            })
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return response()->json([
            'sales_today' => (float) $sales,
            'tickets_today' => $tickets,
            'avg_ticket_today' => $avgTicket,
            'online_sales_today' => (float) $onlineSales,
            // Forzar objeto JSON (assoc array); sin esto Laravel puede serializar la coleccion como lista
            'sales_by_payment_method' => $salesByMethod->map(fn ($v) => (float) $v)->toArray(),
        ]);
    }

    public function byLocation(): JsonResponse
    {
        $rows = Transaction::query()
            ->select('location_id', DB::raw('COUNT(*) as tickets'), DB::raw('SUM(total) as sales'))
            ->groupBy('location_id')
            ->get();

        return response()->json($rows);
    }
}
