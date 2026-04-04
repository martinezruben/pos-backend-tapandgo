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
        'full_name',
        'role',
        'is_active',
        'location_id',
    ];

    protected $hidden = ['password', 'remember_token', 'pin_sha384'];

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
}
