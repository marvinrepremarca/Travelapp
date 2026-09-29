<?php

declare(strict_types=1);

return [
    'holidays' => [
        'new_year' => 'Año Nuevo',
        'epiphany' => 'Día de los Reyes Magos',
        'saint_joseph' => 'Día de San José',
        'holy_thursday' => 'Jueves Santo',
        'good_friday' => 'Viernes Santo',
        'labour_day' => 'Día del Trabajo',
        'ascension' => 'Ascensión del Señor',
        'corpus_christi' => 'Corpus Christi',
        'sacred_heart' => 'Sagrado Corazón',
        'saint_peter_and_paul' => 'San Pedro y San Pablo',
        'independence_day' => 'Día de la Independencia',
        'battle_of_boyaca' => 'Batalla de Boyacá',
        'assumption' => 'La Asunción de la Virgen',
        'columbus_day' => 'Día de la Raza',
        'all_saints' => 'Todos los Santos',
        'independence_of_cartagena' => 'Independencia de Cartagena',
        'immaculate_conception' => 'Día de la Inmaculada Concepción',
        'christmas' => 'Navidad',
    ],
    'holiday_source' => [
        'national' => 'Nacional',
        'agency' => 'De la agencia',
    ],
    'holiday_adjustment_type' => [
        'add' => 'Agregar día no hábil',
        'remove' => 'Trabajar en festivo nacional',
    ],
    'settings' => [
        'agency' => [
            'timezone' => [
                'label' => 'Zona horaria de la agencia',
                'help' => 'Se usa para mostrar fechas y horas internas. Las fechas de servicio se muestran en la zona del destino.',
            ],
            'default_currency' => [
                'label' => 'Moneda principal',
                'help' => 'Código ISO 4217 (p. ej. COP, USD).',
            ],
        ],
        'quotes' => [
            'validity_hours' => [
                'label' => 'Vigencia de las cotizaciones (horas)',
                'help' => 'Al vencer, los precios de proveedores externos quedan por revalidar.',
            ],
        ],
        'bookings' => [
            'on_request_response_sla_hours' => [
                'label' => 'Tiempo de respuesta para reservas bajo petición (horas)',
                'help' => 'Plazo para que el proveedor confirme una reserva que no es inmediata.',
            ],
        ],
        'visibility' => [
            'travel_agent_scope' => [
                'label' => 'Qué expedientes ve un asesor',
                'help' => 'Solo los suyos o todos los de su sucursal.',
            ],
            'hide_margins_from_agents' => [
                'label' => 'Ocultar márgenes a los asesores',
                'help' => 'Si está activo, los asesores no ven el margen de la venta.',
            ],
        ],
    ],
    'errors' => [
        'business_days_negative' => 'La cantidad de días hábiles no puede ser negativa.',
        'not_a_national_holiday' => 'El :date no es festivo nacional; no se puede marcar como día laborable.',
        'already_a_national_holiday' => 'El :date ya es festivo nacional.',
    ],
];
