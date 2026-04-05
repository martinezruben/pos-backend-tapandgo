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

    /**
     * Pantallas con modelo (excl. dashboard), ordenadas por etiqueta, para la matriz RBAC.
     *
     * @return list<array{key: string, label: string, readonly: bool}>
     */
    public static function managedScreens(): array
    {
        $out = [];
        foreach (array_keys(config('admin_screens', [])) as $screen) {
            if ($screen === 'dashboard') {
                continue;
            }
            $cfg = config("admin_screens.$screen");
            if (! is_array($cfg) || empty($cfg['model'])) {
                continue;
            }
            $out[] = [
                'key' => $screen,
                'label' => $cfg['label'] ?? $screen,
                'readonly' => ! empty($cfg['readonly']),
            ];
        }
        usort($out, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));

        return $out;
    }

    /**
     * Permisos asignables en la matriz por pantalla (solo view si la pantalla es solo lectura).
     *
     * @return list<string>
     */
    public static function managedPermissionNamesForScreen(string $screen): array
    {
        $cfg = config("admin_screens.$screen");
        if (! is_array($cfg) || empty($cfg['model'])) {
            return [];
        }
        $p = self::permissionsForScreen($screen);
        if (! empty($cfg['readonly'])) {
            return [$p['view']];
        }

        return [$p['view'], $p['edit'], $p['delete']];
    }

    /**
     * Todos los permisos que la matriz RBAC puede activar o quitar en un rol.
     *
     * @return list<string>
     */
    public static function allManagedPermissionNames(): array
    {
        $names = [];
        foreach (self::managedScreens() as $s) {
            $names = array_merge($names, self::managedPermissionNamesForScreen($s['key']));
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
