<?php

use App\Models\AdminUser;
use App\Models\ApiRequestLog;
use App\Models\Device;
use App\Models\Family;
use App\Models\License;
use App\Models\Location;
use App\Models\NcfSequence;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Subfamily;
use App\Models\SyncLog;
use App\Models\SyncState;
use App\Models\SystemParameter;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\TransactionPayment;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'chart-bar'],
    'locations' => [
        'model' => Location::class,
        'label' => 'Localidades',
        'icon' => 'map-pin',
        'fields' => ['name', 'address', 'latitude', 'longitude', 'is_active', 'last_sync_at'],
        'grid' => [
            'filters' => [
                'is_active' => [
                    'label' => 'Activo',
                    'type' => 'select',
                    'options' => ['' => 'Todos', '1' => 'Sí', '0' => 'No'],
                    'apply' => ['type' => 'column', 'column' => 'is_active'],
                ],
            ],
            'sortable' => ['name', 'address', 'latitude', 'longitude', 'is_active', 'last_sync_at'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
    'devices' => [
        'model' => Device::class,
        'label' => 'Dispositivos',
        'icon' => 'device-phone-mobile',
        'disable_create' => true,
        'labels' => [
            'registered_at' => 'Fecha de registro',
        ],
        'fields' => ['location_id', 'device_fingerprint', 'name', 'is_enabled', 'registered_at', 'last_sync_at'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
            ],
            'sortable' => ['location_id', 'device_fingerprint', 'name', 'is_enabled', 'registered_at', 'last_sync_at'],
            'default_sort' => ['key' => 'updated_at', 'direction' => 'desc'],
        ],
    ],
    'licenses' => [
        'model' => License::class,
        'label' => 'Licencias',
        'icon' => 'key',
        'labels' => [
            'id' => 'ID licencia (clave)',
            'valid_from' => 'Válida desde',
            'valid_to' => 'Caducidad (válida hasta)',
            'status' => 'Estado',
        ],
        'fields' => ['id', 'device_id', 'location_name', 'valid_from', 'valid_to', 'status'],
        'select_options' => [
            'status' => [
                'ACTIVE' => 'ACTIVE (Activa)',
                'INACTIVE' => 'INACTIVE (Inactiva)',
                'EXPIRED' => 'EXPIRED (Caducada)',
                'REVOKED' => 'REVOKED (Revocada)',
            ],
        ],
        'foreign_labels' => [
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
            'location_name' => [
                'virtual' => true,
                'chain' => ['device', 'location'],
                'attribute' => 'name',
                'header' => 'Localidad',
            ],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'whereHas', 'relation' => 'device', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
                'status' => [
                    'label' => 'Estado',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'ACTIVE' => 'ACTIVE', 'EXPIRED' => 'EXPIRED', 'REVOKED' => 'REVOKED'],
                    'apply' => ['type' => 'column', 'column' => 'status'],
                ],
            ],
            'sortable' => ['id', 'location_name', 'device_id', 'status', 'valid_from', 'valid_to'],
            'default_sort' => ['key' => 'created_at', 'direction' => 'desc'],
        ],
    ],
    'android-users' => [
        'model' => User::class,
        'label' => 'Usuarios POS (TapGo)',
        'icon' => 'users',
        'labels' => [
            'username' => 'Usuario',
            'full_name' => 'Nombre completo',
            'role' => 'Rol',
            'password' => 'Contraseña',
            'is_active' => 'Activo',
            'location_id' => 'Localidad',
            'last_activity_at' => 'Última actividad',
        ],
        'select_options' => [
            'role' => [
                'CASHIER' => 'Cajero (CASHIER)',
                'MANAGER' => 'Gerente (MANAGER)',
                'ADMIN' => 'Administrador (ADMIN)',
            ],
        ],
        'fields' => ['username', 'full_name', 'role', 'is_active', 'location_id', 'last_activity_at', 'password'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'last_activity_at' => ['virtual' => true, 'header' => 'Última actividad'],
        ],
        'grid' => [
            'exclude_from_grid' => ['password'],
            'visible_limit' => 12,
            'filters' => [
                'role' => [
                    'label' => 'Rol',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'CASHIER' => 'CASHIER', 'MANAGER' => 'MANAGER', 'ADMIN' => 'ADMIN'],
                    'apply' => ['type' => 'column', 'column' => 'role'],
                ],
                'is_active' => [
                    'label' => 'Activo',
                    'type' => 'select',
                    'options' => ['' => 'Todos', '1' => 'Sí', '0' => 'No'],
                    'apply' => ['type' => 'column', 'column' => 'is_active'],
                ],
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
            ],
            'sortable' => ['username', 'full_name', 'role', 'is_active', 'location_id', 'last_activity_at'],
            'default_sort' => ['key' => 'username', 'direction' => 'asc'],
        ],
    ],
    'families' => [
        'model' => Family::class,
        'label' => 'Familias',
        'icon' => 'squares-2x2',
        'labels' => [
            'image_url' => 'Imagen',
        ],
        'fields' => ['name', 'image_url'],
        'foreign_labels' => [
            'image_url' => ['virtual' => true, 'header' => 'Imagen'],
        ],
        'grid' => [
            'filters' => [],
            'sortable' => ['name'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
            'visible_limit' => 8,
        ],
    ],
    'subfamilies' => [
        'model' => Subfamily::class,
        'label' => 'Subfamilias',
        'icon' => 'queue-list',
        'fields' => ['family_id', 'name'],
        'foreign_labels' => [
            'family_id' => ['relation' => 'family', 'attribute' => 'name', 'header' => 'Familia'],
        ],
        'grid' => [
            'filters' => [
                'family_id' => [
                    'label' => 'Familia',
                    'type' => 'select',
                    'model' => Family::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'family_id'],
                ],
            ],
            'sortable' => ['family_id', 'name'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
    'products' => [
        'model' => Product::class,
        'label' => 'Productos',
        'icon' => 'cube',
        'labels' => [
            'sku' => 'SKU',
            'barcode' => 'Código de barras',
            'name' => 'Nombre',
            'price' => 'Precio',
            'tax_rate' => 'IVA (%)',
            'is_active' => 'Activo',
            'is_favorite' => 'Favorito',
            'image_url' => 'Imagen',
        ],
        'fields' => ['sku', 'barcode', 'name', 'subfamily_id', 'price', 'tax_rate', 'is_active', 'is_favorite', 'image_url'],
        'foreign_labels' => [
            'subfamily_id' => ['relation' => 'subfamily', 'attribute' => 'admin_label', 'header' => 'Subfamilia'],
        ],
        'grid' => [
            'filters' => [
                'subfamily_id' => [
                    'label' => 'Subfamilia',
                    'type' => 'select',
                    'model' => Subfamily::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'subfamily_id'],
                ],
                'is_active' => [
                    'label' => 'Activo',
                    'type' => 'select',
                    'options' => ['' => 'Todos', '1' => 'Sí', '0' => 'No'],
                    'apply' => ['type' => 'column', 'column' => 'is_active'],
                ],
                'is_favorite' => [
                    'label' => 'Favorito',
                    'type' => 'select',
                    'options' => ['' => 'Todos', '1' => 'Sí', '0' => 'No'],
                    'apply' => ['type' => 'column', 'column' => 'is_favorite'],
                ],
            ],
            'sortable' => ['sku', 'barcode', 'name', 'subfamily_id', 'price', 'tax_rate', 'is_active', 'is_favorite'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
    'shifts' => [
        'model' => Shift::class,
        'label' => 'Turnos',
        'icon' => 'clock',
        'fields' => ['location_id', 'device_id', 'user_id', 'shift_number', 'start_time', 'end_time', 'opening_balance', 'closing_balance'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
            'user_id' => ['relation' => 'user', 'attribute' => 'full_name', 'fallback_attribute' => 'username', 'header' => 'Usuario'],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
                'user_id' => [
                    'label' => 'Usuario',
                    'type' => 'select',
                    'model' => User::class,
                    'order_by' => 'full_name',
                    'label_column' => 'full_name',
                    'fallback_column' => 'username',
                    'apply' => ['type' => 'column', 'column' => 'user_id'],
                ],
            ],
            'sortable' => ['location_id', 'device_id', 'user_id', 'shift_number', 'start_time', 'end_time'],
            'default_sort' => ['key' => 'start_time', 'direction' => 'desc'],
        ],
    ],
    'transactions' => [
        'model' => Transaction::class,
        'label' => 'Transacciones',
        'icon' => 'banknotes',
        'labels' => [
            'occurred_at' => 'Fecha / hora',
            'items_count' => 'Líneas',
        ],
        'fields' => ['occurred_at', 'external_id', 'location_id', 'device_id', 'shift_id', 'user_id', 'status', 'total', 'items_count', 'is_synced', 'synced_at'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
            'shift_id' => ['relation' => 'shift', 'attribute' => 'shift_number', 'header' => 'Turno', 'prefix' => '#', 'fallback_to_own_column' => true],
            'user_id' => ['relation' => 'user', 'attribute' => 'full_name', 'fallback_attribute' => 'username', 'header' => 'Usuario'],
            'items_count' => ['virtual' => true, 'header' => 'Líneas'],
        ],
        'grid' => [
            'visible_limit' => 12,
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
                'status' => [
                    'label' => 'Estado',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'PENDING' => 'PENDING', 'PAID' => 'PAID', 'VOIDED' => 'VOIDED'],
                    'apply' => ['type' => 'column', 'column' => 'status'],
                ],
            ],
            'sortable' => ['external_id', 'location_id', 'device_id', 'shift_id', 'user_id', 'status', 'total', 'occurred_at', 'items_count'],
            'default_sort' => ['key' => 'occurred_at', 'direction' => 'desc'],
        ],
    ],
    'transaction-items' => [
        'exclude_from_nav' => true,
        'model' => TransactionItem::class,
        'label' => 'Items Transacción',
        'icon' => 'queue-list',
        'fields' => ['transaction_id', 'product_id', 'product_name', 'product_sku', 'qty', 'unit_price', 'discount', 'tax', 'line_total'],
        'foreign_labels' => [
            'transaction_id' => ['relation' => 'transaction', 'attribute' => 'external_id', 'header' => 'Transacción'],
            'product_id' => ['relation' => 'product', 'attribute' => 'name', 'fallback_attribute' => 'sku', 'header' => 'Producto'],
        ],
        'grid' => [
            'filters' => [
                'transaction_id' => [
                    'label' => 'Transacción',
                    'type' => 'select',
                    'model' => Transaction::class,
                    'order_by' => 'occurred_at',
                    'label_column' => 'external_id',
                    'apply' => ['type' => 'column', 'column' => 'transaction_id'],
                ],
                'product_id' => [
                    'label' => 'Producto',
                    'type' => 'select',
                    'model' => Product::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'sku',
                    'apply' => ['type' => 'column', 'column' => 'product_id'],
                ],
            ],
            'sortable' => ['transaction_id', 'product_id', 'product_name', 'qty', 'line_total'],
            'default_sort' => ['key' => 'updated_at', 'direction' => 'desc'],
        ],
    ],
    'transaction-payments' => [
        'exclude_from_nav' => true,
        'model' => TransactionPayment::class,
        'label' => 'Pagos Transacción',
        'icon' => 'credit-card',
        'fields' => ['transaction_id', 'payment_method', 'amount', 'reference'],
        'foreign_labels' => [
            'transaction_id' => ['relation' => 'transaction', 'attribute' => 'external_id', 'header' => 'Transacción'],
        ],
        'grid' => [
            'filters' => [
                'transaction_id' => [
                    'label' => 'Transacción',
                    'type' => 'select',
                    'model' => Transaction::class,
                    'order_by' => 'occurred_at',
                    'label_column' => 'external_id',
                    'apply' => ['type' => 'column', 'column' => 'transaction_id'],
                ],
                'payment_method' => [
                    'label' => 'Método',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'CASH' => 'CASH', 'CARD' => 'CARD', 'TRANSFER' => 'TRANSFER', 'OTHER' => 'OTHER'],
                    'apply' => ['type' => 'column', 'column' => 'payment_method'],
                ],
            ],
            'sortable' => ['transaction_id', 'payment_method', 'amount'],
            'default_sort' => ['key' => 'created_at', 'direction' => 'desc'],
        ],
    ],
    'sync-states' => [
        'model' => SyncState::class,
        'label' => 'Estado de Sincronización',
        'icon' => 'arrow-path',
        'fields' => ['location_id', 'device_id', 'last_pull_at', 'last_push_at', 'last_success_at', 'last_error_at', 'last_error_message'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
            ],
            'sortable' => ['location_id', 'device_id', 'last_pull_at', 'last_push_at', 'last_success_at'],
            'default_sort' => ['key' => 'updated_at', 'direction' => 'desc'],
        ],
    ],
    'sync-logs' => [
        'model' => SyncLog::class,
        'label' => 'Logs de Sincronización',
        'icon' => 'clipboard-document-list',
        'fields' => ['location_id', 'device_id', 'operation', 'entity', 'records_count', 'status', 'started_at', 'finished_at', 'error_message'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
                'status' => [
                    'label' => 'Estado',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'SUCCESS' => 'SUCCESS', 'FAILED' => 'FAILED'],
                    'apply' => ['type' => 'column', 'column' => 'status'],
                ],
                'operation' => [
                    'label' => 'Operación',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'PUSH' => 'PUSH', 'PULL' => 'PULL'],
                    'apply' => ['type' => 'column', 'column' => 'operation'],
                ],
            ],
            'sortable' => ['location_id', 'device_id', 'operation', 'status', 'started_at', 'records_count'],
            'default_sort' => ['key' => 'started_at', 'direction' => 'desc'],
        ],
    ],
    'api-request-logs' => [
        'model' => ApiRequestLog::class,
        'label' => 'Llamadas API',
        'icon' => 'arrow-path',
        'readonly' => true,
        'labels' => [
            'created_at' => 'Fecha/hora',
            'method' => 'Método',
            'path' => 'Ruta',
            'parameters' => 'Parámetros',
            'response_status' => 'HTTP',
            'response_summary' => 'Respuesta',
            'location_id' => 'Localidad',
            'device_id' => 'Dispositivo',
            'device_fingerprint' => 'Huella',
            'ip_address' => 'IP',
            'duration_ms' => 'ms',
        ],
        'fields' => [
            'created_at', 'method', 'path', 'parameters', 'response_status', 'response_summary', 'location_id', 'device_id', 'device_fingerprint', 'ip_address', 'duration_ms',
        ],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad'],
            'device_id' => ['relation' => 'device', 'attribute' => 'name', 'fallback_attribute' => 'device_fingerprint', 'header' => 'Dispositivo'],
        ],
        'grid' => [
            'filters' => [
                'location_id' => [
                    'label' => 'Localidad',
                    'type' => 'select',
                    'model' => Location::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'apply' => ['type' => 'column', 'column' => 'location_id'],
                ],
                'device_id' => [
                    'label' => 'Dispositivo',
                    'type' => 'select',
                    'model' => Device::class,
                    'order_by' => 'name',
                    'label_column' => 'name',
                    'fallback_column' => 'device_fingerprint',
                    'apply' => ['type' => 'column', 'column' => 'device_id'],
                ],
                'method' => [
                    'label' => 'Método',
                    'type' => 'select',
                    'options' => ['' => 'Todos', 'GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE'],
                    'apply' => ['type' => 'column', 'column' => 'method'],
                ],
                'response_status' => [
                    'label' => 'HTTP',
                    'type' => 'select',
                    'options' => [
                        '' => 'Todos',
                        '200' => '200',
                        '401' => '401',
                        '403' => '403',
                        '422' => '422',
                        '429' => '429',
                        '500' => '500',
                    ],
                    'apply' => ['type' => 'column', 'column' => 'response_status'],
                ],
            ],
            'sortable' => ['created_at', 'method', 'path', 'response_status', 'duration_ms', 'location_id', 'device_id'],
            'search_columns' => ['method', 'path', 'parameters', 'response_summary', 'device_fingerprint', 'ip_address'],
            'default_sort' => ['key' => 'created_at', 'direction' => 'desc'],
        ],
    ],
    'system-settings' => [
        'model' => SystemParameter::class,
        'label' => 'Parámetros del sistema',
        'icon' => 'cube',
        'exclude_from_nav' => true,
        'fields' => ['id'],
        'readonly' => true,
        'disable_create' => true,
        'grid' => [
            'filters' => [],
            'sortable' => [],
            'default_sort' => ['key' => 'id', 'direction' => 'asc'],
        ],
    ],
    'ncf-sequences' => [
        'model' => NcfSequence::class,
        'label' => 'NCF (Series de consecutivos)',
        'icon' => 'identifier',
        'labels' => [
            'type'           => 'Tipo',
            'establishment'  => 'Establecimiento',
            'start'          => 'Inicio',
            'end'            => 'Fin',
            'current'        => 'Contador actual',
            'location_id'    => 'Localidad',
        ],
        'enable_toggle_check' => true,
        'fields' => ['type', 'establishment', 'location_id', 'start', 'end', 'current'],
        'foreign_labels' => [
            'location_id' => ['relation' => 'location', 'attribute' => 'name', 'header' => 'Localidad', 'fallback_attribute' => 'code'],
        ],
        'grid' => [
            'filters' => [
                'type'        => ['label' => 'Tipo', 'type' => 'select', 'options' => ['01' => 'Venta 01', '04' => 'NC 04', '05' => 'ND 05', '07' => 'Guía 07', 'E31' => 'RD Bienes', 'E32' => 'RD Servicios', 'E33' => 'RD Comb.', 'E34' => 'RD Imp.'], 'apply' => ['type' => 'column', 'column' => 'type']],
                'location_id' => ['label' => 'Localidad', 'type' => 'select', 'model' => Location::class, 'order_by' => 'name', 'label_column' => 'name', 'apply' => ['type' => 'column', 'column' => 'location_id']],
            ],
            'sortable' => ['type', 'establishment', 'location_id', 'start', 'end', 'current'],
            'default_sort' => ['key' => 'type', 'direction' => 'asc'],
        ],
    ],
    'settings' => [
        'model' => \App\Models\SystemParameter::class,
        'label' => 'Configuración del Sistema',
        'icon' => 'cog-6-tooth',
        'single' => true,
        'enable_toggle_check' => false,
        'labels' => [
            'admin_password_min_length' => 'Min. caracteres password admin',
            'admin_max_failed_login_attempts' => 'Máx. intentos fallidos login',
            'admin_lockout_minutes' => 'Minutos de bloqueo login',
        ],
        'fields' => ['admin_password_min_length', 'admin_max_failed_login_attempts', 'admin_lockout_minutes'],
        'grid' => [
            'visible_limit' => 3,
        ],
    ],
    'admin-users' => [
        'model' => AdminUser::class,
        'label' => 'Usuarios Backend',
        'icon' => 'shield-check',
        'fields' => ['name', 'email', 'is_active', 'password'],
        'grid' => [
            'filters' => [
                'is_active' => [
                    'label' => 'Activo',
                    'type' => 'select',
                    'options' => ['' => 'Todos', '1' => 'Sí', '0' => 'No'],
                    'apply' => ['type' => 'column', 'column' => 'is_active'],
                ],
            ],
            'sortable' => ['name', 'email', 'is_active'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
    'roles' => [
        'model' => Role::class,
        'label' => 'Roles',
        'icon' => 'user-group',
        'fields' => ['name'],
        'grid' => [
            'filters' => [],
            'sortable' => ['name'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
    'permissions' => [
        'model' => Permission::class,
        'label' => 'Permisos',
        'icon' => 'lock-closed',
        'fields' => ['name'],
        'grid' => [
            'filters' => [],
            'sortable' => ['name'],
            'default_sort' => ['key' => 'name', 'direction' => 'asc'],
        ],
    ],
];
