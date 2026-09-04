<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Subfamily;
use App\Models\SyncLog;
use App\Models\SyncState;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Services\ImageThumbnailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SyncController extends Controller
{
    /**
     * Cada transacción: `user_id` = UUID de un usuario POS existente (`users.id`); `status` ∈ PENDING, PAID, VOIDED;
     * `shift_id` = identificador de turno enviado por el cliente (texto libre, opcional), sin validar contra `shifts`.
     * `occurred_at` en ISO 8601 o timestamp Unix (segundos o milisegundos), igual que `lastSyncTimestamp` en pull.
     */
    public function push(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $payload = $request->validate([
            'transactions' => ['required', 'array'],
            'transactions.*.external_id' => ['required', 'string'],
            'transactions.*.shift_id' => ['nullable', 'string', 'max:100'],
            'transactions.*.user_id' => ['required', 'uuid', Rule::exists('users', 'id')],
            'transactions.*.turn_number' => ['required', 'integer'],
            'transactions.*.status' => ['required', 'string', Rule::in(['PENDING', 'PAID', 'VOIDED'])],
            'transactions.*.total' => ['required', 'numeric'],
            'transactions.*.occurred_at' => ['required'],
            'transactions.*.items' => ['array'],
            'transactions.*.payments' => ['array'],
        ]);

        foreach ($payload['transactions'] as $i => $txData) {
            if (($txData['shift_id'] ?? null) === '') {
                $payload['transactions'][$i]['shift_id'] = null;
            }
        }

        foreach ($payload['transactions'] as $i => $txData) {
            if (! is_string($txData['occurred_at'] ?? null)
                && ! is_int($txData['occurred_at'] ?? null)
                && ! is_float($txData['occurred_at'] ?? null)) {
                return response()->json([
                    'message' => 'El campo transactions.'.$i.'.occurred_at debe ser texto o número (timestamp).',
                    'errors' => [
                        'transactions.'.$i.'.occurred_at' => [
                            'El campo debe ser texto o número (timestamp).',
                        ],
                    ],
                ], 422);
            }
        }

        foreach ($payload['transactions'] as $i => $txData) {
            try {
                $raw = $this->normalizeFlexibleTimestampInput($txData['occurred_at']);
                $payload['transactions'][$i]['occurred_at'] = $this->parseFlexibleTimestampToUtc($raw);
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [
                        'transactions.'.$i.'.occurred_at' => [$e->getMessage()],
                    ],
                ], 422);
            }
        }

        $startedAt = now();
        $stored = 0;

        DB::transaction(function () use ($payload, $device, &$stored): void {
            foreach ($payload['transactions'] as $txData) {
                $transaction = Transaction::updateOrCreate(
                    ['device_id' => $device->id, 'external_id' => $txData['external_id']],
                    [
                        'location_id' => $device->location_id,
                        'shift_id' => $txData['shift_id'] ?? null,
                        'user_id' => $txData['user_id'],
                        'turn_number' => $txData['turn_number'],
                        'status' => $txData['status'],
                        'total' => $txData['total'],
                        'occurred_at' => $txData['occurred_at'],
                        'is_synced' => true,
                        'synced_at' => now(),
                    ]
                );

                if ($transaction->wasRecentlyCreated) {
                    $stored++;
                }

                $transaction->items()->delete();
                foreach ($txData['items'] ?? [] as $item) {
                    TransactionItem::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $item['product_name'] ?? 'unknown',
                        'product_sku' => $item['product_sku'] ?? null,
                        'qty' => $item['qty'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'tax' => $item['tax'] ?? 0,
                        'line_total' => $item['line_total'] ?? 0,
                    ]);
                }

                $transaction->payments()->delete();
                foreach ($txData['payments'] ?? [] as $payment) {
                    TransactionPayment::create([
                        'transaction_id' => $transaction->id,
                        'payment_method' => $payment['payment_method'] ?? 'OTHER',
                        'amount' => $payment['amount'] ?? 0,
                        'reference' => $payment['reference'] ?? null,
                    ]);
                }
            }

            $device->update(['last_sync_at' => now()]);
            $device->location->update(['last_sync_at' => now()]);

            SyncState::updateOrCreate(
                ['location_id' => $device->location_id, 'device_id' => $device->id],
                ['last_push_at' => now(), 'last_success_at' => now(), 'last_error_at' => null, 'last_error_message' => null]
            );
        });

        SyncLog::create([
            'location_id' => $device->location_id,
            'device_id' => $device->id,
            'operation' => 'PUSH',
            'entity' => 'transactions',
            'records_count' => $stored,
            'status' => 'SUCCESS',
            'started_at' => $startedAt,
            'finished_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'stored' => $stored,
        ]);
    }

    /**
     * Catálogo y datos maestros para el POS (bajada).
     *
     * Query o cabecera `Device-Fingerprint`: `device_fingerprint` obligatorio (debe coincidir con el dispositivo del token).
     * También se acepta el parámetro `deviceFingerprint` (camelCase).
     * lastSyncTimestamp (opcional): ISO 8601 UTC, o milisegundos Unix, o segundos Unix; solo filas con updatedAt (o deletedAt) posteriores.
     *
     * JSON camelCase alineado a entidades Room: LicenseEntity (incluye `deviceFingerprint`, no UUID de dispositivo),
     * UserEntity, FamilyEntity, SubfamilyEntity, ProductEntity, PaymentMethodEntity (tablas license, users, families,
     * subfamilies, products, payment_methods).
     */
    public function pull(Request $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $this->mergePullDeviceFingerprint($request);

        $validated = $request->validate([
            'device_fingerprint' => ['required', 'string', 'max:255', Rule::in([$device->device_fingerprint])],
            'lastSyncTimestamp' => ['nullable', 'string', 'max:80'],
        ], [
            'device_fingerprint.required' => 'La huella del dispositivo es obligatoria.',
            'device_fingerprint.in' => 'La huella no coincide con el dispositivo autenticado.',
        ]);

        $since = null;
        if (isset($validated['lastSyncTimestamp']) && $validated['lastSyncTimestamp'] !== '') {
            try {
                $since = $this->parseFlexibleTimestampToUtc(trim($validated['lastSyncTimestamp']));
            } catch (InvalidArgumentException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => ['lastSyncTimestamp' => [$e->getMessage()]],
                ], 422);
            }
        }

        $syncTimestamp = now()->utc();
        $syncTimestampIso = $syncTimestamp->format('Y-m-d\TH:i:s').'Z';

        $license = $device->activeLicense;
        abort_unless($license, 500, 'Active license missing.');

        $licensePayload = $this->licensePayload($device, $license);

        $users = $this->pullUsersForLocation($device->location_id, $since)->map(fn (User $u) => $this->userPayload($u));

        $families = $this->pullFamilies($since)->map(fn (Family $f) => $this->familyPayload($f));

        $subfamilies = $this->pullSubfamilies($since)->map(fn (Subfamily $s) => $this->subfamilyPayload($s));

        $products = $this->pullProducts($since)->map(fn (Product $p) => $this->productPayload($p));

        $promotions = $this->pullPromotions($since)->map(fn (Promotion $pr) => $this->promotionPayload($pr));

        $paymentMethods = $this->paymentMethodsPayload($syncTimestampIso, $since);

        $recordCount = 1 + $users->count() + $families->count() + $subfamilies->count() + $products->count() + $promotions->count() + count($paymentMethods);

        SyncState::updateOrCreate(
            ['location_id' => $device->location_id, 'device_id' => $device->id],
            ['last_pull_at' => now(), 'last_success_at' => now(), 'last_error_at' => null, 'last_error_message' => null]
        );

        SyncLog::create([
            'location_id' => $device->location_id,
            'device_id' => $device->id,
            'operation' => 'PULL',
            'entity' => 'catalog',
            'records_count' => $recordCount,
            'status' => 'SUCCESS',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $device->update(['last_sync_at' => now()]);
        $device->location->update(['last_sync_at' => now()]);

        return response()->json([
            'syncTimestamp' => $syncTimestampIso,
            'data' => [
                'license' => $licensePayload,
                'users' => $users->values()->all(),
                'families' => $families->values()->all(),
                'subfamilies' => $subfamilies->values()->all(),
                'products' => $products->values()->all(),
                'promotions' => $promotions->values()->all(),
                'paymentMethods' => $paymentMethods,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function licensePayload(Device $device, License $license): array
    {
        $active = $license->status === 'ACTIVE'
            && $license->valid_from <= now()
            && $license->valid_to >= now();

        return [
            'deviceFingerprint' => $device->device_fingerprint,
            'isActive' => $active,
            'plan' => config('sync_catalog.license_plan_default', 'standard'),
            'expiresAt' => $license->valid_to->utc()->format('Y-m-d\TH:i:s').'Z',
            'updatedAt' => $license->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
        ];
    }

    private function pullUsersForLocation(string $locationId, ?Carbon $since)
    {
        $q = User::withTrashed()
            ->where(function ($w) use ($locationId): void {
                $w->whereHas('locations', fn ($rel) => $rel->where('locations.id', $locationId))
                    ->orWhere('users.location_id', $locationId);
            });

        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('users.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('users.deleted_at')
                            ->where('users.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('users.updated_at')->get();
    }

    /**
     * @return Collection<int, Family>
     */
    private function pullFamilies(?Carbon $since)
    {
        $q = Family::withTrashed();
        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('families.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('families.deleted_at')
                            ->where('families.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('updated_at')->get();
    }

    /**
     * @return Collection<int, Subfamily>
     */
    private function pullSubfamilies(?Carbon $since)
    {
        $q = Subfamily::withTrashed()->with('family');
        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('subfamilies.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('subfamilies.deleted_at')
                            ->where('subfamilies.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('updated_at')->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function pullProducts(?Carbon $since)
    {
        $q = Product::withTrashed()->with(['subfamily.family']);
        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('products.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('products.deleted_at')
                            ->where('products.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('updated_at')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $u): array
    {
        return [
            'id' => $u->id,
            'username' => $u->username,
            // pin_sha384: SHA-384 hex del texto en claro (trim), para validación offline; fallback legacy si aún no existe.
            'pin' => $u->pin_sha384 ?? hash('sha384', $u->getAuthPassword()),
            'role' => strtolower((string) $u->role),
            'isActive' => (bool) $u->is_active,
            'updatedAt' => $u->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $u->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function familyPayload(Family $f): array
    {
        return [
            'id' => $f->id,
            'name' => $f->name,
            'imageUrl' => ImageThumbnailService::syncUrl($f->image_url),
            'updatedAt' => $f->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $f->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function subfamilyPayload(Subfamily $s): array
    {
        return [
            'id' => $s->id,
            'familyId' => $s->family_id,
            'name' => $s->name,
            'updatedAt' => $s->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $s->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @return Collection<int, Promotion>
     */
    private function pullPromotions(?Carbon $since): Collection
    {
        $q = Promotion::withTrashed();
        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('promotions.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('promotions.deleted_at')
                            ->where('promotions.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('promotions.updated_at')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function promotionPayload(Promotion $pr): array
    {
        $scope = $pr->scopeAttribute();
        $scopeType = $scope['scopeType'];
        $scopeId = $scope['scopeId'];

        return [
            'id' => $pr->id,
            'name' => $pr->name,
            'type' => $pr->type,
            'value' => (float) $pr->value,
            'buyQty' => $pr->buy_qty !== null ? (float) $pr->buy_qty : null,
            'payQty' => $pr->pay_qty !== null ? (float) $pr->pay_qty : null,
            'scopeType' => $scopeType,
            'scopeId' => $scopeId,
            'scopeName' => $pr->displayScopeName(),
            'startsAt' => $pr->starts_at?->utc()->format('Y-m-d\TH:i:s').'Z',
            'endsAt' => $pr->ends_at?->utc()->format('Y-m-d\TH:i:s').'Z',
            'isActive' => (bool) $pr->is_active,
            'updatedAt' => $pr->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $pr->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function productPayload(Product $p): array
    {
        $sub = $p->subfamily;
        $fam = $sub?->family;
        $taxRateFraction = round(((float) $p->tax_rate) / 100, 4);

        $sku = $p->sku !== null && $p->sku !== '' ? $p->sku : null;
        $desc = $p->description !== null && $p->description !== '' ? $p->description : null;
        $barcodeVal = $p->barcode !== null && $p->barcode !== '' ? $p->barcode : null;
        $familyName = $fam?->name ?? '';
        $isFavorite = (bool) $p->is_favorite;

        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $desc,
            'sku' => $sku,
            'codebar' => $barcodeVal,
            'imageUrl' => ImageThumbnailService::syncUrl($p->image_url),
            'familyName' => $familyName,
            'categoria' => $familyName,
            'subfamilyName' => $sub?->name ?? '',
            'unitPrice' => (float) $p->price,
            'taxName' => $p->tax_rate > 0 ? 'IVA '.rtrim(rtrim(number_format((float) $p->tax_rate, 2, '.', ''), '0'), '.').'%' : 'Sin impuesto',
            'taxRate' => $taxRateFraction,
            'isFavorite' => $isFavorite,
            'updatedAt' => $p->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $p->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Métodos de pago desde BD (gestionables en el panel). El POS renderiza
     * un botón por método usando name/type/color.
     *
     * @return list<array<string, mixed>>
     */
    private function paymentMethodsPayload(string $syncTimestampIso, ?Carbon $since): array
    {
        $q = PaymentMethod::withTrashed();
        if ($since === null) {
            $q->whereNull('deleted_at');
        } else {
            $q->where(function ($w) use ($since): void {
                $w->where('payment_methods.updated_at', '>', $since)
                    ->orWhere(function ($w2) use ($since): void {
                        $w2->whereNotNull('payment_methods.deleted_at')
                            ->where('payment_methods.deleted_at', '>', $since);
                    });
            });
        }

        return $q->orderBy('name')->get()->map(fn (PaymentMethod $pm) => [
            'id' => $pm->id,
            'name' => $pm->name,
            'type' => $pm->type,
            'color' => $pm->color,
            'isEnabled' => (bool) $pm->is_enabled,
            'updatedAt' => $pm->updated_at->utc()->format('Y-m-d\TH:i:s').'Z',
            'deletedAt' => $pm->deleted_at?->utc()->format('Y-m-d\TH:i:s\Z'),
        ])->all();
    }

    /**
     * Convierte entrada de cliente (string / int / float) a string para {@see parseFlexibleTimestampToUtc()}.
     */
    private function normalizeFlexibleTimestampInput(string|int|float $value): string
    {
        if (is_string($value)) {
            return trim($value);
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (floor($value) == $value) {
            return sprintf('%.0f', $value);
        }

        return (string) $value;
    }

    /**
     * Acepta ISO 8601, milisegundos Unix (≥13 dígitos o valor ≥ 1e12), o segundos Unix.
     * Usado en pull (`lastSyncTimestamp`) y push (`occurred_at`).
     */
    private function parseFlexibleTimestampToUtc(string $raw): Carbon
    {
        $s = trim($raw);
        if ($s === '') {
            throw new InvalidArgumentException('El valor no puede estar vacío.');
        }

        if (preg_match('/^\d+$/', $s)) {
            $n = (int) $s;

            if (strlen($s) >= 13 || $n >= 1_000_000_000_000) {
                return Carbon::createFromTimestampMs($n, 'UTC');
            }

            return Carbon::createFromTimestamp($n, 'UTC');
        }

        try {
            return Carbon::parse($s)->utc();
        } catch (\Throwable) {
            throw new InvalidArgumentException('No es una fecha ISO válida ni un timestamp numérico (segundos o milisegundos).');
        }
    }

    /**
     * Cabecera HTTP `Device-Fingerprint`, o query `device_fingerprint` / `deviceFingerprint`.
     */
    private function mergePullDeviceFingerprint(Request $request): void
    {
        $h = $request->header('Device-Fingerprint');
        if (is_string($h) && $h !== '') {
            $request->merge(['device_fingerprint' => $h]);

            return;
        }

        foreach (['device_fingerprint', 'deviceFingerprint'] as $key) {
            $v = $request->input($key);
            if (is_string($v) && $v !== '') {
                $request->merge(['device_fingerprint' => $v]);

                return;
            }
        }
    }
}
