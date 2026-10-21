<?php

declare(strict_types=1);

/*
 * Capacidades de negocio activables (ADR-0007). Apagar una capacidad oculta sus pantallas, rutas y menú;
 * nunca borra datos. `php artisan capabilities:status --check` valida las dependencias antes de desplegar.
 */
return [
    'enabled' => [
        'commercial' => (bool) env('CAPABILITY_COMMERCIAL_ENABLED', true),
        'quoting' => (bool) env('CAPABILITY_QUOTING_ENABLED', true),
        'own_product' => (bool) env('CAPABILITY_OWN_PRODUCT_ENABLED', true),
        'bookings' => (bool) env('CAPABILITY_BOOKINGS_ENABLED', true),
        'operations' => (bool) env('CAPABILITY_OPERATIONS_ENABLED', true),
        'collections' => (bool) env('CAPABILITY_COLLECTIONS_ENABLED', true),
        'accounting' => (bool) env('CAPABILITY_ACCOUNTING_ENABLED', true),
        'invoicing' => (bool) env('CAPABILITY_INVOICING_ENABLED', true),
        'messaging' => (bool) env('CAPABILITY_MESSAGING_ENABLED', true),
        'portals' => (bool) env('CAPABILITY_PORTALS_ENABLED', true),
        'compliance' => (bool) env('CAPABILITY_COMPLIANCE_ENABLED', true),
    ],

    /*
     * Fecha (AAAA-MM-DD, zona de la agencia) desde la que una capacidad reprocesa lo ocurrido mientras estuvo
     * apagada. Vacía = toda la historia. Útil al contratar una capacidad tiempo después: p. ej. Contabilidad
     * desde el 1 de enero, con los saldos anteriores cargados a mano, sin traer las ventas del año pasado.
     */
    'replay_since' => [
        'commercial' => env('CAPABILITY_COMMERCIAL_REPLAY_SINCE'),
        'quoting' => env('CAPABILITY_QUOTING_REPLAY_SINCE'),
        'own_product' => env('CAPABILITY_OWN_PRODUCT_REPLAY_SINCE'),
        'bookings' => env('CAPABILITY_BOOKINGS_REPLAY_SINCE'),
        'operations' => env('CAPABILITY_OPERATIONS_REPLAY_SINCE'),
        'collections' => env('CAPABILITY_COLLECTIONS_REPLAY_SINCE'),
        'accounting' => env('CAPABILITY_ACCOUNTING_REPLAY_SINCE'),
        'invoicing' => env('CAPABILITY_INVOICING_REPLAY_SINCE'),
        'messaging' => env('CAPABILITY_MESSAGING_REPLAY_SINCE'),
        'portals' => env('CAPABILITY_PORTALS_REPLAY_SINCE'),
        'compliance' => env('CAPABILITY_COMPLIANCE_REPLAY_SINCE'),
    ],

    // Reproceso de eventos pendientes al encender una capacidad (bitácora de integración).
    'catch_up' => [
        'cron' => env('CAPABILITY_CATCH_UP_CRON', '*/5 * * * *'),
        'chunk' => (int) env('CAPABILITY_CATCH_UP_CHUNK', 100),
        'lock_seconds' => (int) env('CAPABILITY_CATCH_UP_LOCK_SECONDS', 120),
    ],

    // Retención de la bitácora de integración: los datos de negocio no se tocan.
    'retention' => [
        'delivered_days' => (int) env('CAPABILITY_EVENTS_DELIVERED_RETENTION_DAYS', 90),
        'pending_days' => (int) env('CAPABILITY_EVENTS_PENDING_RETENTION_DAYS', 730),
        'cron' => env('CAPABILITY_EVENTS_PRUNE_CRON', '30 3 * * *'),
        'chunk' => (int) env('CAPABILITY_EVENTS_PRUNE_CHUNK', 500),
    ],
];
