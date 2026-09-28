<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getProductsBestSellers(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?string $locationId = null)
    {
        $dateFrom = $dateFrom ?? now()->subMonth();
        $dateTo = $dateTo ?? now();

        $query = Product::select(
            'products.id',
            'products.name',
            'products.sku',
            'products.tax_rate',
            DB::raw('COALESCE(SUM(transaction_items.qty), 0) as sold_qty'),
            DB::raw('COALESCE(SUM(transaction_items.line_total), 0) as total_revenue'),
            DB::raw('CASE WHEN COALESCE(SUM(transaction_items.qty), 0) > 0 THEN COALESCE(SUM(transaction_items.line_total), 0) / SUM(transaction_items.qty) ELSE 0 END as avg_price')
        )
            ->leftJoin('transaction_items', 'products.id', '=', 'transaction_items.product_id')
            ->leftJoin('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'PAID')
            ->whereBetween('transactions.occurred_at', [$dateFrom, $dateTo])
            ->when($locationId, fn ($q) => $q->where('transactions.location_id', $locationId))
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.tax_rate')
            ->orderBy('sold_qty', 'DESC')
            ->get();

        return $query;
    }

    public function getPaymentMethodsReport(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?string $locationId = null)
    {
        $dateFrom = $dateFrom ?? now()->subMonth();
        $dateTo = $dateTo ?? now();

        // Por fecha de la venta (no de sincronización del pago) y solo ventas cobradas
        $totals = DB::table('transaction_payments')
            ->join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'PAID')
            ->whereBetween('transactions.occurred_at', [$dateFrom, $dateTo])
            ->when($locationId, fn ($q) => $q->where('transactions.location_id', $locationId))
            ->select(
                'transaction_payments.payment_method as name',
                DB::raw('COUNT(DISTINCT transaction_payments.transaction_id) as total_transactions'),
                DB::raw('COALESCE(SUM(transaction_payments.amount), 0) as total_amount')
            )
            ->groupBy('transaction_payments.payment_method')
            ->get();

        $grandTotal = $totals->sum('total_amount') ?: 1;
        $labels = PaymentMethod::labelsFor($totals->pluck('name'));

        $totals = $totals->map(fn ($item) => [
            'name' => $labels[$item->name] ?? $item->name,
            'total_transactions' => $item->total_transactions,
            'total_amount' => $item->total_amount,
            'percentage' => ($item->total_amount / $grandTotal) * 100,
        ]);

        return $totals->sortByDesc('total_amount')->values();
    }

    public function getUsersPerformanceReport(?Carbon $dateFrom = null, ?Carbon $dateTo = null, ?string $locationId = null)
    {
        $dateFrom = $dateFrom ?? now()->subMonth();
        $dateTo = $dateTo ?? now();

        $users = User::select(
            'users.id',
            'users.username',
            'users.full_name',
            DB::raw('COUNT(DISTINCT transactions.id) as transaction_count'),
            DB::raw('COALESCE(SUM(transactions.total), 0) as total_sales'),
            DB::raw('CASE WHEN COUNT(DISTINCT transactions.id) > 0 THEN COALESCE(SUM(transactions.total), 0) / COUNT(DISTINCT transactions.id) ELSE 0 END as avg_transaction'),
            DB::raw('MAX(transactions.occurred_at) as last_activity')
        )
            ->leftJoin('transactions', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.status', 'PAID')
            ->whereBetween('transactions.occurred_at', [$dateFrom, $dateTo])
            ->when($locationId, fn ($q) => $q->where('transactions.location_id', $locationId))
            ->where('users.is_active', true)
            ->groupBy('users.id', 'users.username', 'users.full_name')
            ->orderBy('total_sales', 'DESC')
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'transaction_count' => $user->transaction_count,
                'total_sales' => $user->total_sales,
                'avg_transaction' => $user->avg_transaction,
                'last_activity' => is_string($user->last_activity) ? (strtotime($user->last_activity) ? now()->parse($user->last_activity)->diffForHumans() : 'N/A') : ($user->last_activity ? $user->last_activity->diffForHumans() : 'N/A'),
            ]);

        return $users;
    }
}
