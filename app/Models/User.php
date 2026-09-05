<?php

namespace App\Models;

use App\Models\Traits\HasUuidPrimaryKey;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuidPrimaryKey, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'pin_sha384',
        'pin4_sha384',
        'full_name',
        'role',
        'is_active',
        'location_id',
    ];

    protected $hidden = ['password', 'remember_token', 'pin_sha384', 'pin4_sha384'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'user_locations')->withTimestamps();
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * SHA-384 hexadecimal (96 chars) del PIN/contraseña en claro, con trim.
     * La app Android debe aplicar la misma normalización al validar en local.
     */
    public static function pinSha384FromPlain(string $plain): string
    {
        return hash('sha384', trim($plain));
    }

    /**
     * SHA-384 hexadecimal del PIN de 4 dígitos en claro (trim).
     * Verificación offline en el dispositivo; el servidor nunca lo guarda en claro.
     */
    public static function pin4Sha384FromPlain(string $plain): string
    {
        return hash('sha384', trim($plain));
    }

    /** PINs de 4 dígitos demasiado comunes/degutes para permitirlos. */
    public static function isWeakPin4(string $pin): bool
    {
        if (preg_match('/^(\d)\1{3}$/', $pin)) {
            return true; // 0000, 1111, …
        }
        if (preg_match('/^(0123|1234|2345|3456|4567|5678|6789|9876|8765|4321)$/', $pin)) {
            return true; // secuencias
        }

        return in_array($pin, ['1004', '2000', '2580', '1122', '1313'], true);
    }
}
