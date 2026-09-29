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

    'security' => [
        'two_factor_required_roles' => ['system_admin', 'agency_owner', 'finance'],
    ],

];
