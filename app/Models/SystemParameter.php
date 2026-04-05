<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemParameter extends Model
{
    protected $fillable = [
        'admin_password_min_length',
        'admin_password_require_uppercase',
        'admin_password_require_lowercase',
        'admin_password_require_digit',
        'admin_password_require_symbol',
        'pos_password_min_length',
        'pos_password_require_uppercase',
        'pos_password_require_lowercase',
        'pos_password_require_digit',
        'pos_password_require_symbol',
        'admin_max_failed_login_attempts',
        'admin_lockout_minutes',
    ];

    protected function casts(): array
    {
        return [
            'admin_password_require_uppercase' => 'boolean',
            'admin_password_require_lowercase' => 'boolean',
            'admin_password_require_digit' => 'boolean',
            'admin_password_require_symbol' => 'boolean',
            'pos_password_require_uppercase' => 'boolean',
            'pos_password_require_lowercase' => 'boolean',
            'pos_password_require_digit' => 'boolean',
            'pos_password_require_symbol' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
