<?php

declare(strict_types=1);

use Laravel\Fortify\Features;

/*
| Sistema interno: sin registro público. Los usuarios los crea un administrador
| (Fase 1.2). El perfil se edita desde el módulo Identity.
*/

return [

    'guard' => 'web',

    'passwords' => 'users',

    'username' => 'email',

    'email' => 'email',

    'lowercase_usernames' => true,

    'home' => '/' . trim((string) env('APP_PATH_PREFIX', ''), '/'),

    'prefix' => trim((string) env('APP_PATH_PREFIX', ''), '/'),

    'redirects' => [
        'logout' => '/' . trim((string) env('APP_PATH_PREFIX', ''), '/'),
    ],

    'domain' => null,

    'middleware' => ['web'],

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'attempts_per_minute' => [
        'login' => (int) env('AUTH_LOGIN_ATTEMPTS_PER_MINUTE', 5),
        'two_factor' => (int) env('AUTH_TWO_FACTOR_ATTEMPTS_PER_MINUTE', 5),
    ],

    'views' => true,

    'features' => [
        Features::resetPasswords(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],

];
