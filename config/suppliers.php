<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Proveedores externos (ADR-0003)
|--------------------------------------------------------------------------
| Solo se llama a hosts en `allowed_hosts` (anti-SSRF). Cada proveedor define
| timeouts y reintentos; las credenciales (cuando existan) van en .env → services.
*/

return [

    'allowed_hosts' => [
        'www.datos.gov.co',
    ],

    'providers' => [

        'datos_gov_trm' => [
            'base_url' => env('TRM_SOURCE_URL', 'https://www.datos.gov.co/resource/32sa-8pi3.json'),
            'connect_timeout_seconds' => (int) env('TRM_CONNECT_TIMEOUT', 3),
            'timeout_seconds' => (int) env('TRM_TIMEOUT', 10),
            'retries' => (int) env('TRM_RETRIES', 3),
            'retry_sleep_ms' => (int) env('TRM_RETRY_SLEEP_MS', 500),
        ],

    ],

];
