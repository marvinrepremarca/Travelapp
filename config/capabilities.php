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

    // Reproceso de eventos pendientes al encender una capacidad (bitácora de integración).
    'catch_up' => [
        'cron' => env('CAPABILITY_CATCH_UP_CRON', '*/5 * * * *'),
        'chunk' => (int) env('CAPABILITY_CATCH_UP_CHUNK', 100),
        'lock_seconds' => (int) env('CAPABILITY_CATCH_UP_LOCK_SECONDS', 120),
    ],
];
