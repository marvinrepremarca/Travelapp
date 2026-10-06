<?php

declare(strict_types=1);

return [
    'title' => 'Auditoría',
    'system' => 'Sistema',
    'changed_to' => 'cambió a',
    'empty_title' => 'Sin registros',
    'empty_description' => 'No hay registros para los filtros elegidos.',
    'tabs' => [
        'changes' => 'Cambios',
        'security' => 'Seguridad',
        'sensitive_access' => 'Accesos a datos sensibles',
    ],
    'log_names' => [
        'organization' => 'Agencia y sucursales',
        'identity' => 'Usuarios',
        'security' => 'Seguridad',
        'workflow' => 'Tareas y aprobaciones',
        'crm' => 'Clientes',
        'suppliers' => 'Proveedores',
        'pricing' => 'Precios y tasas',
        'catalog' => 'Catálogo',
        'quotes' => 'Cotizaciones',
        'bookings' => 'Expedientes',
        'payments' => 'Pagos',
        'finance' => 'Finanzas',
        'invoicing' => 'Facturación',
        'communications' => 'Comunicaciones',
        'reports' => 'Reportes',
    ],
    'filters' => [
        'module' => 'Módulo',
        'all_modules' => 'Todos los módulos',
        'from' => 'Desde',
        'to' => 'Hasta',
    ],
    'columns' => [
        'when' => 'Fecha',
        'user' => 'Usuario',
        'action' => 'Acción',
        'detail' => 'Detalle',
        'subject' => 'Registro',
        'field' => 'Dato',
        'reason' => 'Motivo',
    ],
    'events' => [
        'created' => 'Creó',
        'updated' => 'Modificó',
        'deleted' => 'Eliminó',
    ],
    'security_events' => [
        'logged_in' => 'Inicio de sesión',
        'logged_out' => 'Cierre de sesión',
        'login_failed' => 'Intento fallido',
        'locked_out' => 'Bloqueo por intentos',
        'password_reset' => 'Contraseña restablecida',
        'two_factor_enabled' => '2FA activada',
        'two_factor_disabled' => '2FA desactivada',
        'two_factor_failed' => 'Código 2FA incorrecto',
    ],
    'access_types' => [
        'viewed' => 'Consultó',
        'exported' => 'Exportó',
        'downloaded' => 'Descargó',
    ],
    'errors' => [
        'immutable' => 'Los registros de auditoría no se pueden modificar ni borrar.',
    ],
];
