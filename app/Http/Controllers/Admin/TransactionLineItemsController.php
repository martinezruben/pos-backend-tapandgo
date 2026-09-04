<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Support\AdminRbac;
use Illuminate\Http\JsonResponse;

class TransactionLineItemsController extends Controller
{
    public function show(Transaction $transaction): JsonResponse
    {
        $screen = 'transactions';
        $user = auth('admin')->user();
        abort_unless($user, 403);
        $p = AdminRbac::permissionsForScreen($screen);
        abort_unless($user->can($p['view']), 403);

        $transaction->load(['items', 'payments', 'location', 'device', 'user']);

        return response()->json([
            'id' => (string) $transaction->getKey(),
            'external_id' => $transaction->external_id,
            'occurred_at' => $transaction->occurred_at?->format('Y-m-d H:i:s'),
            'status' => $transaction->status,
            'total' => (string) $transaction->total,
            'payment_methods' => $transaction->payments->pluck('payment_method')->unique()->values()->all(),
            'location_name' => $transaction->location?->name,
            'device_label' => $transaction->device !== null
                ? (($transaction->device->name ?? '') !== ''
                    ? $transaction->device->name
                    : $transaction->device->device_fingerprint)
                : null,
            'items' => $transaction->items->map(fn ($i) => [
                'product_name' => $i->product_name,
                'product_sku' => $i->product_sku,
                'qty' => (string) $i->qty,
                'unit_price' => (string) $i->unit_price,
                'discount' => (string) $i->discount,
                'tax' => (string) $i->tax,
                'line_total' => (string) $i->line_total,
            ])->values(),
            'payments' => $transaction->payments->map(fn ($p) => [
                'payment_method' => $p->payment_method,
                'amount' => (string) $p->amount,
                'reference' => $p->reference,
            ])->values(),
        ]);
    }
}
