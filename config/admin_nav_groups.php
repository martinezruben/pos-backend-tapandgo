<?php

/**
 * Grupos del sidebar (estilo Snow: secciones colapsables con ítems indentados).
 *
 * Cada pantalla con CRUD en routes/web.php (`admin.screens.*`) debe aparecer aquí,
 * salvo vistas solo-detalle (p. ej. líneas/pagos de transacción): esas llevan
 * `exclude_from_nav` en config/admin_screens.php y no se listan.
 */
return [
    [
        'key' => 'infra',
        'label' => 'Infraestructura',
        'screens' => ['locations', 'devices', 'licenses'],
    ],
    [
        'key' => 'catalog',
        'label' => 'Catálogo',
        'screens' => ['families', 'subfamilies', 'promotions', 'products'],
    ],
    [
        'key' => 'ops',
        'label' => 'Operación',
        'screens' => ['android-users', 'shifts', 'transactions', 'ncf-sequences'],
    ],
    [
        'key' => 'sync',
        'label' => 'Sincronización',
        'screens' => ['sync-states', 'sync-logs', 'api-request-logs'],
    ],
    [
        'key' => 'system',
        'label' => 'Sistema',
        'screens' => ['admin-users', 'roles', 'permissions', 'audit-log'],
    ],
    [
        'key' => 'reports',
        'label' => 'Reportes',
        'screens' => ['ncf-report', 'transactions-report', 'cierre-caja'],
    ],
];
