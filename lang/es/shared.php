<?php

declare(strict_types=1);

return [
    'loading' => 'Cargando…',
    'skip_to_content' => 'Saltar al contenido',
    'main_navigation' => 'Navegación principal',
    'dashboard' => 'Inicio',
    'dashboard_empty_title' => 'Bienvenido a TravelApp',
    'dashboard_empty_description' => 'Aquí verás tus indicadores y pendientes cuando los módulos estén activos.',
    'visibility_scope' => [
        'own' => 'Propios',
        'branch' => 'Sucursal',
        'all' => 'Toda la agencia',
    ],
    'passenger_type' => [
        'adult' => 'Adulto',
        'child' => 'Niño',
        'infant' => 'Infante',
    ],
    'product_type' => [
        'flight' => 'Vuelo',
        'hotel' => 'Hotel',
        'car' => 'Auto',
        'transfer' => 'Traslado',
        'tour' => 'Tour',
        'day_trip' => 'Pasadía',
        'activity' => 'Actividad',
        'insurance' => 'Seguro de viaje',
        'package' => 'Paquete',
    ],
    'sales_channel' => [
        'branch' => 'Sucursal',
        'phone' => 'Teléfono',
        'whatsapp' => 'WhatsApp',
        'online' => 'Tienda en línea',
        'partner' => 'Agencia aliada',
        'corporate' => 'Corporativo',
    ],
    'errors' => [
        'money_expected' => 'El atributo :key debe ser un importe con moneda.',
        'date_range_end_before_start' => 'La fecha final (:end) no puede ser anterior a la inicial (:start).',
        'passenger_mix_no_adults' => 'Debe viajar al menos un adulto.',
        'passenger_mix_too_many_infants' => 'Hay :infants infantes y :adults adultos: cada infante debe viajar con un adulto.',
        'passenger_mix_negative' => 'La cantidad de pasajeros y las edades no pueden ser negativas.',
        'split_parts_positive' => 'El número de partes debe ser al menos 1.',
        'split_ratios_invalid' => 'Las proporciones deben ser enteros no negativos con suma mayor que cero.',
    ],
];
