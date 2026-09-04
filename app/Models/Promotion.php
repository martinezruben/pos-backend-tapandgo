<?php

namespace App\Models;

use App\Models\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Promoción parametrizada que viaja al POS por sync/pull.
 * Scope: exactamente una de product_id / subfamily_id / family_id;
 * todas nulas = promoción global (todos los productos).
 */
class Promotion extends Model
{
    use HasUuidPrimaryKey, SoftDeletes;

    protected $fillable = ['name', 'type', 'value', 'buy_qty', 'pay_qty', 'product_id', 'subfamily_id', 'family_id', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'buy_qty' => 'decimal:2',
            'pay_qty' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function subfamily()
    {
        return $this->belongsTo(Subfamily::class);
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /** @return array{scopeType: string, scopeId: ?string} */
    public function scopeAttribute(): array
    {
        return match (true) {
            $this->product_id !== null => ['scopeType' => 'PRODUCT', 'scopeId' => $this->product_id],
            $this->subfamily_id !== null => ['scopeType' => 'SUBFAMILY', 'scopeId' => $this->subfamily_id],
            $this->family_id !== null => ['scopeType' => 'FAMILY', 'scopeId' => $this->family_id],
            default => ['scopeType' => 'GLOBAL', 'scopeId' => null],
        };
    }

    /** Nombre legible del objetivo según scope (para el payload del POS). */
    public function displayScopeName(): string
    {
        return match (true) {
            $this->product_id !== null => (string) ($this->product?->name ?? $this->product_id),
            $this->subfamily_id !== null => (string) ($this->subfamily?->name ?? $this->subfamily_id),
            $this->family_id !== null => (string) ($this->family?->name ?? $this->family_id),
            default => 'Todos los productos',
        };
    }
}
