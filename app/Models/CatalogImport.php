<?php

namespace App\Models;

use App\Models\Traits\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

/** Registro de cada carga de catálogo (resumen + informe Excel descargable). */
class CatalogImport extends Model
{
    use HasUuidPrimaryKey;

    protected $fillable = ['admin_user_id', 'admin_name', 'file_name', 'create_missing', 'summary', 'report_path'];

    protected function casts(): array
    {
        return ['summary' => 'array', 'create_missing' => 'boolean'];
    }
}
