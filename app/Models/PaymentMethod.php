<?php

namespace App\Models;

use App\Models\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Método de pago gestionable desde el panel; viaja al POS por sync/pull
 * para renderizar un botón por método. Los IDs legacy (pm-cash…) se
 * conservaron al migrar desde config/sync_catalog.php.
 */
class PaymentMethod extends Model
{
    use HasUuidPrimaryKey, SoftDeletes;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'type', 'color', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }
}
