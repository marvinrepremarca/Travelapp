<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Registro de proveedores externos (ADR-0003)
|--------------------------------------------------------------------------
| Cada entrada: 'codigo' => ['adapter' => Clase::class, 'products' => [ProductType...],
| 'enabled' => bool, 'timeout_seconds' => int, 'retries' => int,
| 'circuit_breaker' => ['failures' => int, 'cooldown_seconds' => int]].
| Las credenciales viven en config/services.php leyendo .env.
*/

return [

    'allowed_hosts' => [],

    'providers' => [],

];
