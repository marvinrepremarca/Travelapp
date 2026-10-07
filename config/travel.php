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
        'number_prefix' => env('TRAVEL_QUOTE_NUMBER_PREFIX', 'COT-'),
        'number_digits' => (int) env('TRAVEL_QUOTE_NUMBER_DIGITS', 6),
        'max_options' => (int) env('TRAVEL_QUOTE_MAX_OPTIONS', 5),
        'max_passengers_per_item' => (int) env('TRAVEL_QUOTE_MAX_PASSENGERS_PER_ITEM', 50),
        'max_passenger_age' => (int) env('TRAVEL_QUOTE_MAX_PASSENGER_AGE', 120),
        'max_nights' => (int) env('TRAVEL_QUOTE_MAX_NIGHTS', 90),
        'per_page' => (int) env('TRAVEL_QUOTES_PER_PAGE', 20),
        'customer_link_requests_per_minute' => (int) env('TRAVEL_QUOTE_LINK_REQUESTS_PER_MINUTE', 30),
    ],

    'bookings' => [
        'number_prefix' => env('TRAVEL_BOOKING_NUMBER_PREFIX', 'EXP-'),
        'number_digits' => (int) env('TRAVEL_BOOKING_NUMBER_DIGITS', 6),
        'per_page' => (int) env('TRAVEL_BOOKINGS_PER_PAGE', 20),
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

    'finance' => [
        // Proveedores en prepago: se les paga esta cantidad de días antes del servicio.
        'prepaid_days_before_service' => (int) env('TRAVEL_PREPAID_DAYS_BEFORE_SERVICE', 7),
        'per_page' => (int) env('TRAVEL_FINANCE_PER_PAGE', 25),
        'cash_history_size' => (int) env('TRAVEL_CASH_HISTORY_SIZE', 10),
        // Conciliación: días de diferencia aceptados entre el banco y el sistema para sugerir un cruce.
        'reconciliation_window_days' => (int) env('TRAVEL_RECONCILIATION_WINDOW_DAYS', 3),
        'statement_max_kb' => (int) env('TRAVEL_STATEMENT_MAX_KB', 2048),
        // Formato por defecto del CSV del extracto (se ajusta por cuenta bancaria).
        'statement_defaults' => [
            'delimiter' => ',',
            'date_format' => 'd/m/Y',
            'decimal_separator' => '.',
            'header_row' => 1,
        ],
    ],

    'invoicing' => [
        // Prefijos del consecutivo interno por tipo de documento (la resolución DIAN llega con la facturación electrónica).
        'prefixes' => [
            'invoice' => env('TRAVEL_INVOICE_PREFIX', 'FV-'),
            'credit_note' => env('TRAVEL_CREDIT_NOTE_PREFIX', 'NC-'),
            'debit_note' => env('TRAVEL_DEBIT_NOTE_PREFIX', 'ND-'),
        ],
        'e_invoicing_provider' => env('TRAVEL_E_INVOICING_PROVIDER', 'internal'),
        'submit_tries' => (int) env('TRAVEL_E_INVOICING_TRIES', 5),
        'submit_backoff_seconds' => [30, 120, 600, 1800],
        'ready_candidates_limit' => (int) env('TRAVEL_INVOICING_READY_LIMIT', 100),
        'per_page' => (int) env('TRAVEL_INVOICING_PER_PAGE', 25),
    ],

    'communications' => [
        // Canal de WhatsApp activo (cambiar de proveedor = cambiar .env).
        'whatsapp_channel' => env('TRAVEL_WHATSAPP_CHANNEL', 'fake_whatsapp'),
        'default_country_code' => env('TRAVEL_WHATSAPP_COUNTRY_CODE', '+57'),
        'bot_date_format' => 'd/m/Y',
        // Fechas en los avisos (en el idioma de la aplicación).
        'notice_date_format' => 'j \d\e F \d\e Y',
        'notice_datetime_format' => 'j \d\e F \d\e Y, g:i a',
        // Palabras con las que el cliente pide un asesor o indica viaje solo de ida (sin tildes, minúsculas).
        'agent_words' => ['asesor', 'agente', 'humano', 'persona'],
        'one_way_words' => ['no', 'solo ida', 'ninguno'],
        'max_message_length' => (int) env('TRAVEL_WHATSAPP_MAX_LENGTH', 1000),
        'send_tries' => (int) env('TRAVEL_WHATSAPP_SEND_TRIES', 5),
        'send_backoff_seconds' => [10, 60, 300, 900],
        'webhook_requests_per_minute' => (int) env('TRAVEL_WHATSAPP_WEBHOOKS_PER_MINUTE', 240),
        'inbox_size' => (int) env('TRAVEL_WHATSAPP_INBOX_SIZE', 50),
        'inbox_poll_seconds' => (int) env('TRAVEL_WHATSAPP_POLL_SECONDS', 5),
        'simulator_enabled' => (bool) env('TRAVEL_WHATSAPP_SIMULATOR', true),
        'simulator_default_phone' => env('TRAVEL_WHATSAPP_SIMULATOR_PHONE', '+57 300 123 4567'),
        'simulator_history' => (int) env('TRAVEL_WHATSAPP_SIMULATOR_HISTORY', 50),
        // Recordatorio de saldo: días antes de la fecha límite de pago y hora del envío diario.
        'balance_reminder_days_before' => (int) env('TRAVEL_BALANCE_REMINDER_DAYS_BEFORE', 3),
        'balance_reminder_time' => env('TRAVEL_BALANCE_REMINDER_TIME', '09:00'),
    ],

    'portal' => [
        // Vigencia del enlace mágico de "Mi viaje" y límites contra abuso.
        'link_ttl_hours' => (int) env('TRAVEL_PORTAL_LINK_TTL_HOURS', 168),
        'requests_per_minute' => (int) env('TRAVEL_PORTAL_REQUESTS_PER_MINUTE', 60),
        'access_attempts_per_hour' => (int) env('TRAVEL_PORTAL_ACCESS_ATTEMPTS', 5),
    ],

    'operations' => [
        // Días que muestra el tablero de salidas y capacidad máxima de un vehículo.
        'board_days' => (int) env('TRAVEL_OPERATIONS_BOARD_DAYS', 7),
        'max_vehicle_capacity' => (int) env('TRAVEL_OPERATIONS_MAX_VEHICLE_CAPACITY', 60),
    ],

    'reports' => [
        'month_label_format' => 'F Y',
        'list_size' => (int) env('TRAVEL_REPORTS_LIST_SIZE', 8),
        'top_size' => (int) env('TRAVEL_REPORTS_TOP_SIZE', 10),
        // "Vence pronto" en cartera y cuentas por pagar.
        'due_soon_days' => (int) env('TRAVEL_REPORTS_DUE_SOON_DAYS', 7),
        'expiring_quote_days' => (int) env('TRAVEL_REPORTS_EXPIRING_QUOTE_DAYS', 3),
        'upcoming_trip_days' => (int) env('TRAVEL_REPORTS_UPCOMING_TRIP_DAYS', 14),
        // Expedientes vigentes que se revisan para la cartera (protege el tiempo de la pantalla).
        'receivables_scan_limit' => (int) env('TRAVEL_REPORTS_RECEIVABLES_LIMIT', 500),
        'export_max_rows' => (int) env('TRAVEL_REPORTS_EXPORT_MAX_ROWS', 5000),
    ],

    'payments' => [
        'gateway' => env('TRAVEL_PAYMENT_GATEWAY', 'fake'),
        // El saldo debe estar pago esta cantidad de días antes del primer servicio.
        'balance_due_days_before' => (int) env('TRAVEL_BALANCE_DUE_DAYS_BEFORE', 15),
        'link_ttl_hours' => (int) env('TRAVEL_PAYMENT_LINK_TTL_HOURS', 48),
        'webhook_requests_per_minute' => (int) env('TRAVEL_PAYMENT_WEBHOOKS_PER_MINUTE', 120),
    ],

    // Búsqueda multi-proveedor (ADR-0006): cambiar de proveedor = cambiar estas listas en .env.
    'search' => [
        'flight_providers' => array_values(array_filter(explode(',', (string) env('TRAVEL_FLIGHT_PROVIDERS', 'fake')))),
        'hotel_providers' => array_values(array_filter(explode(',', (string) env('TRAVEL_HOTEL_PROVIDERS', 'fake')))),
        'cache_ttl_seconds' => (int) env('TRAVEL_SEARCH_CACHE_TTL', 600),
        'breaker_failure_threshold' => (int) env('TRAVEL_SEARCH_BREAKER_FAILURES', 3),
        'breaker_cooldown_seconds' => (int) env('TRAVEL_SEARCH_BREAKER_COOLDOWN', 120),
        'max_passengers' => (int) env('TRAVEL_SEARCH_MAX_PASSENGERS', 9),
        'max_nights' => (int) env('TRAVEL_SEARCH_MAX_NIGHTS', 30),
        // Autocompletar de lugares: letras mínimas para sugerir y cantidad de sugerencias.
        'autocomplete_min_chars' => (int) env('TRAVEL_SEARCH_AUTOCOMPLETE_MIN_CHARS', 2),
        'autocomplete_limit' => (int) env('TRAVEL_SEARCH_AUTOCOMPLETE_LIMIT', 8),
    ],

    // Presupuestos de rendimiento (regla performance.md); los verifica tests/Feature/Shared/PerformanceBudgetTest.
    'performance' => [
        'max_queries_per_screen' => (int) env('TRAVEL_MAX_QUERIES_PER_SCREEN', 15),
        'max_duplicate_queries_per_screen' => 0,
    ],

    'security' => [
        // Roles con 2FA obligatorio (lista separada por comas). Vacío solo en local/pruebas manuales; en producción nunca.
        'two_factor_required_roles' => array_values(array_filter(array_map(trim(...), explode(',', (string) env('TRAVEL_TWO_FACTOR_REQUIRED_ROLES', 'system_admin,agency_owner,finance'))))),
    ],

];
