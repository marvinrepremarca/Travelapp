<?php

declare(strict_types=1);

return [
    'names' => [
        'commercial' => 'Comercial',
        'quoting' => 'Cotizaciones',
        'own_product' => 'Producto propio',
        'bookings' => 'Reservas',
        'operations' => 'Operación en destino',
        'collections' => 'Cobros',
        'accounting' => 'Contabilidad y finanzas',
        'invoicing' => 'Facturación',
        'messaging' => 'Mensajería',
        'portals' => 'Portales',
        'compliance' => 'Cumplimiento',
    ],
    'descriptions' => [
        'commercial' => 'Embudo de prospectos: captura por WhatsApp, web o asesor, seguimiento y conversión en cliente.',
        'quoting' => 'Búsqueda de vuelos y hoteles, cotizaciones con versiones, envío al cliente y aceptación.',
        'own_product' => 'Catálogo de productos propios (tours, pasadías, paquetes) con temporadas, tarifas y cupos.',
        'bookings' => 'Expedientes: servicios confirmados con el proveedor, pasajeros, vouchers e itinerario.',
        'operations' => 'Salidas, manifiestos y asignación de guías y vehículos en destino.',
        'collections' => 'Abonos, links de pago, transferencias y saldos por cobrar del cliente.',
        'accounting' => 'Caja, cuentas por pagar a proveedores, conciliación bancaria y rentabilidad.',
        'invoicing' => 'Facturas, notas crédito y débito, listas para facturación electrónica.',
        'messaging' => 'Bandeja de WhatsApp y avisos automáticos al cliente.',
        'portals' => 'Portal del viajero (Mi viaje) y tienda B2C. Necesita Reservas.',
        'compliance' => 'Obligaciones legales, documentos de la agencia y solicitudes de datos personales.',
    ],
    'guide' => [
        'title' => 'Capacidades del sistema',
        'intro' => 'El sistema está organizado en capacidades de negocio que se encienden o apagan por separado. Apagar una oculta sus pantallas y su menú sin afectar a las demás y sin borrar información.',
        'list_label' => 'Estado de cada capacidad en esta instalación',
        'core_title' => 'Núcleo',
        'core' => 'Agencia y sucursales, usuarios y permisos, clientes y viajeros, proveedores, precios y monedas, documentos, auditoría, tareas y aprobaciones.',
        'always_on' => 'siempre encendido',
        'progress_title' => 'Lo que ya funciona',
        'progress' => [
            'Cada capacidad se enciende o apaga por configuración del despliegue; una pantalla apagada no aparece en el menú y responde "no encontrada".',
            'Cotizaciones opera con normalidad aunque Contabilidad, Facturación y Cobros estén apagadas.',
            'Pagos y mensajes que llegan de pasarelas o de WhatsApp se reciben siempre, aunque su capacidad esté apagada.',
            'Todo evento de negocio queda en una bitácora: al encender Contabilidad, Facturación o Cobros, reconocen en orden lo ocurrido mientras estuvieron apagadas, sin duplicar.',
            'Los avisos al cliente que se perdieron su momento (WhatsApp, enlace del portal) no se envían tarde.',
            'Antes de desplegar se valida que ninguna capacidad encendida dependa de una apagada.',
        ],
        'how_to' => 'Para cambiar las capacidades activas, el administrador técnico ajusta la configuración del despliegue y verifica con la comprobación de capacidades.',
    ],
    'missing_requirement' => ':capability necesita :required, que está apagada',
    'invalid_configuration' => 'Configuración de capacidades inválida: :problems',
    'catch_up' => [
        'done' => 'Eventos pendientes procesados: :count',
    ],
    'status' => [
        'capability' => 'Capacidad',
        'state' => 'Estado',
        'requires' => 'Requiere',
        'on' => 'encendida',
        'off' => 'apagada',
        'ok' => 'Configuración de capacidades válida.',
    ],
];
