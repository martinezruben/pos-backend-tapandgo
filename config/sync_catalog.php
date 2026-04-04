<?php

/**
 * Catálogo enviado en GET /api/sync/pull → data.paymentMethods.
 * Alineado a PaymentMethodEntity (Room): id, name, type, isEnabled, updatedAt, deletedAt.
 */
return [
    'license_plan_default' => env('SYNC_LICENSE_PLAN_DEFAULT', 'standard'),

    'payment_methods' => [
        [
            'id' => 'pm-cash',
            'type' => 'CASH',
            'name' => 'Efectivo',
            'is_enabled' => true,
        ],
        [
            'id' => 'pm-card',
            'type' => 'CARD',
            'name' => 'Tarjeta',
            'is_enabled' => true,
        ],
        [
            'id' => 'pm-transfer',
            'type' => 'TRANSFER',
            'name' => 'Transferencia',
            'is_enabled' => true,
        ],
        [
            'id' => 'pm-other',
            'type' => 'OTHER',
            'name' => 'Otro',
            'is_enabled' => true,
        ],
    ],
];
