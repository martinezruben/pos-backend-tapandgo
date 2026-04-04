<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * RBAC del panel: cada pantalla CRUD usa permisos `{recurso}.view`, `.edit`, `.delete`
 * donde `recurso` es la clave de pantalla con guiones sustituidos por guiones bajos (p. ej. `transaction_items`).
 */
class AdminRbac
{
    public static function resourceKey(string $screen): string
    {
        return str_replace('-', '_', $screen);
    }

    /**
     * @return array{view: string, edit: string, delete: string}
     */
    public static function permissionsForScreen(string $screen): array
    {
        $r = self::resourceKey($screen);

        return [
            'view' => $r.'.view',
            'edit' => $r.'.edit',
            'delete' => $r.'.delete',
        ];
    }

    /**
     * Todos los nombres de permiso CRUD definidos por las pantallas en `admin_screens` (excepto dashboard).
     *
     * @return list<string>
     */
    public static function allCrudPermissionNames(): array
    {
        $names = [];
        foreach (array_keys(config('admin_screens', [])) as $screen) {
            if ($screen === 'dashboard') {
                continue;
            }
            $cfg = config("admin_screens.$screen");
            if (! is_array($cfg) || empty($cfg['model'])) {
                continue;
            }
            $p = self::permissionsForScreen($screen);
            $names[] = $p['view'];
            $names[] = $p['edit'];
            $names[] = $p['delete'];
        }

        return array_values(array_unique($names));
    }

    public static function canAccessScreen(?Authenticatable $user, string $screen): bool
    {
        if (! $user) {
            return false;
        }
        $cfg = config("admin_screens.$screen");
        if (! is_array($cfg) || empty($cfg['model'])) {
            return true;
        }
        $p = self::permissionsForScreen($screen);

        return $user->can($p['view']);
    }
}
