<?php

declare(strict_types=1);

return [
    'fields' => [
        'email' => 'Correo electrónico',
        'password' => 'Contraseña',
        'password_confirmation' => 'Confirmar contraseña',
        'two_factor_code' => 'Código de autenticación',
        'recovery_code' => 'Código de recuperación',
    ],
    'auth' => [
        'login_title' => 'Iniciar sesión',
        'login' => 'Ingresar',
        'logout' => 'Cerrar sesión',
        'remember_me' => 'Recordarme en este equipo',
        'forgot_password' => '¿Olvidaste tu contraseña?',
        'forgot_password_title' => 'Recuperar contraseña',
        'forgot_password_help' => 'Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.',
        'send_reset_link' => 'Enviar enlace',
        'back_to_login' => 'Volver a iniciar sesión',
        'reset_password_title' => 'Nueva contraseña',
        'reset_password' => 'Guardar contraseña',
        'password_rules' => 'Mínimo 12 caracteres, con mayúsculas, minúsculas y números.',
        'two_factor_title' => 'Verificación en dos pasos',
        'two_factor_help' => 'Ingresa el código de tu aplicación de autenticación o uno de tus códigos de recuperación.',
        'verify' => 'Verificar',
        'confirm_password_title' => 'Confirma tu contraseña',
        'confirm_password_help' => 'Por seguridad, confirma tu contraseña para continuar.',
        'confirm' => 'Confirmar',
        'current_password_mismatch' => 'La contraseña actual no es correcta.',
    ],
    'roles' => [
        'system_admin' => 'Administrador del sistema',
        'agency_owner' => 'Gerente de la agencia',
        'branch_manager' => 'Director de sucursal',
        'travel_agent' => 'Asesor de viajes',
        'operations' => 'Operaciones',
        'product_manager' => 'Gestor de producto',
        'finance' => 'Finanzas',
    ],
];
