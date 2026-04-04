<?php

/**
 * Grupos del sidebar (estilo Snow: secciones colapsables con ítems indentados).
 */
return [
    [
        'key' => 'infra',
        'label' => 'Infraestructura',
        'screens' => ['locations', 'devices', 'licenses'],
    ],
    [
        'key' => 'ops',
        'label' => 'Operación',
        'screens' => ['android-users', 'families', 'subfamilies', 'products', 'shifts', 'transactions'],
    ],
    [
        'key' => 'sync',
        'label' => 'Sincronización',
        'screens' => ['sync-states', 'sync-logs', 'api-request-logs'],
    ],
    [
        'key' => 'system',
        'label' => 'Sistema',
        'screens' => ['admin-users', 'roles', 'permissions'],
    ],
];
