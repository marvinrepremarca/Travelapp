<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Parámetros de negocio ⚙ (valores por defecto)
|--------------------------------------------------------------------------
| Editables desde la administración (tabla `settings`) vía el contrato
| `AppSettings` del módulo Organization. Nunca se leen directamente desde
| los módulos de negocio: siempre a través de `AppSettings`.
*/

return [

    'agency' => [
        'timezone' => env('TRAVEL_AGENCY_TIMEZONE', 'America/Bogota'),
        'default_currency' => env('TRAVEL_DEFAULT_CURRENCY', 'COP'),
        'locale' => env('TRAVEL_AGENCY_LOCALE', 'es_CO'),
    ],

    'money' => [
        // Decimales al mostrar/facturar por moneda; si no está aquí se usan los de ISO 4217.
        'presentation_decimals' => [
            'COP' => (int) env('TRAVEL_COP_PRESENTATION_DECIMALS', 0),
        ],
    ],

    'passengers' => [
        // Edad cumplida a la fecha del servicio: infante hasta 1 año (< 2), niño hasta 11 (< 12).
        'infant_max_age' => (int) env('TRAVEL_INFANT_MAX_AGE', 1),
        'child_max_age' => (int) env('TRAVEL_CHILD_MAX_AGE', 11),
    ],

    'quotes' => [
        'validity_hours' => (int) env('TRAVEL_QUOTE_VALIDITY_HOURS', 72),
    ],

    'bookings' => [
        'held_alert_hours' => [48, 24, 4],
        'on_request_response_sla_hours' => (int) env('TRAVEL_ON_REQUEST_SLA_HOURS', 24),
        'require_full_payment_for_non_refundable' => true,
    ],

    'visibility' => [
        'travel_agent_scope' => env('TRAVEL_AGENT_SCOPE', 'own'),
        'hide_margins_from_agents' => (bool) env('TRAVEL_HIDE_MARGINS_FROM_AGENTS', true),
    ],

    'organization' => [
        'logo_disk' => env('TRAVEL_LOGO_DISK', 'public'),
        'logo_directory' => 'branding',
        'logo_max_kb' => (int) env('TRAVEL_LOGO_MAX_KB', 1024),
        // Color del texto que se pinta sobre el color de marca (debe cumplir contraste AA).
        'brand_text_color' => '#ffffff',
        'rnt_expiry_warning_days' => (int) env('TRAVEL_RNT_EXPIRY_WARNING_DAYS', 60),
        'branches_per_page' => (int) env('TRAVEL_BRANCHES_PER_PAGE', 20),
    ],

    'identity' => [
        'users_per_page' => (int) env('TRAVEL_USERS_PER_PAGE', 20),
    ],

    'audit' => [
        'per_page' => (int) env('TRAVEL_AUDIT_PER_PAGE', 25),
    ],

    'workflow' => [
        'per_page' => (int) env('TRAVEL_WORKFLOW_PER_PAGE', 20),
    ],

    'demo' => [
        // Solo para DemoSeeder en local. Nunca se usa en producción.
        'password' => env('TRAVEL_DEMO_PASSWORD', 'ViajesDemo2026'),
    ],

    'crm' => [
        'per_page' => (int) env('TRAVEL_CRM_PER_PAGE', 20),
        'board_column_size' => (int) env('TRAVEL_CRM_BOARD_COLUMN_SIZE', 25),
        'max_lead_travelers' => (int) env('TRAVEL_CRM_MAX_LEAD_TRAVELERS', 500),
        // Vigencia mínima del pasaporte después del regreso (regla habitual de migración) ⚙.
        'passport_min_validity_months' => (int) env('TRAVEL_PASSPORT_MIN_VALIDITY_MONTHS', 6),
    ],

    'suppliers' => [
        'per_page' => (int) env('TRAVEL_SUPPLIERS_PER_PAGE', 20),
        // Días antes del vencimiento del RNT del proveedor para alertar ⚙.
        'rnt_expiry_warning_days' => (int) env('TRAVEL_SUPPLIER_RNT_WARNING_DAYS', 30),
        'max_payment_days' => (int) env('TRAVEL_SUPPLIER_MAX_PAYMENT_DAYS', 180),
    ],

    'pricing' => [
        // Spread de la agencia sobre la tasa oficial (100 = 1 %) ⚙.
        'fx_spread_basis_points' => (int) env('TRAVEL_FX_SPREAD_BASIS_POINTS', 0),
        'per_page' => (int) env('TRAVEL_PRICING_PER_PAGE', 20),
        // Horas (zona de la agencia) en que se intenta descargar la TRM del día.
        'official_rate_fetch_times' => ['06:00', '08:00', '10:00'],
    ],

    'privacy' => [
        // Clave HMAC dedicada para buscar datos cifrados (documentos). Nunca reutilizar APP_KEY.
        'hash_key' => (string) env('TRAVEL_PII_HASH_KEY', ''),
        // Versión vigente de la política de tratamiento de datos que acepta el titular.
        'policy_version' => (string) env('TRAVEL_PRIVACY_POLICY_VERSION', '1.0'),
    ],

    'ui' => [
        // Página de muestra del sistema de diseño; solo para desarrollo y revisión.
        'showcase_enabled' => (bool) env('TRAVEL_UI_SHOWCASE_ENABLED', false),
    ],

    'health' => [
        'requests_per_minute' => (int) env('TRAVEL_HEALTH_REQUESTS_PER_MINUTE', 60),
    ],

    'catalog' => [
        'per_page' => (int) env('TRAVEL_CATALOG_PER_PAGE', 20),
        'departures_per_page' => (int) env('TRAVEL_CATALOG_DEPARTURES_PER_PAGE', 15),
        'max_departure_capacity' => (int) env('TRAVEL_CATALOG_MAX_DEPARTURE_CAPACITY', 500),
        // Día máximo del itinerario de un paquete (0 = primer día).
        'max_package_days' => (int) env('TRAVEL_CATALOG_MAX_PACKAGE_DAYS', 30),
    ],

    // Presupuestos de rendimiento (regla performance.md); los verifica tests/Feature/Shared/PerformanceBudgetTest.
    'performance' => [
        'max_queries_per_screen' => (int) env('TRAVEL_MAX_QUERIES_PER_SCREEN', 15),
        'max_duplicate_queries_per_screen' => 0,
    ],

    'security' => [
        'two_factor_required_roles' => ['system_admin', 'agency_owner', 'finance'],
    ],

];
