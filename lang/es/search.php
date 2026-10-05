<?php

declare(strict_types=1);

return [
    'search' => 'Buscar',
    'searching' => 'Buscando en los proveedores…',
    'ages_hint' => 'Edad de cada pasajero a la fecha del viaje, separadas por coma. Ej.: 35, 33, 8',
    'partial' => 'Resultados parciales: no respondieron :providers.',
    'empty_title' => 'Sin disponibilidad',
    'empty_description' => 'Ningún proveedor tiene opciones para esos criterios. Prueba otras fechas o destinos.',
    'refundable' => 'Reembolsable',
    'non_refundable' => 'No reembolsable',
    'provider' => 'Proveedor: :provider',
    'net' => 'Neto :amount',
    'no_rate' => 'Sin tasa de cambio para calcular la venta',
    'cabin' => [
        'economy' => 'Económica',
        'premium_economy' => 'Económica premium',
        'business' => 'Ejecutiva',
        'first' => 'Primera',
    ],
    'board' => [
        'room_only' => 'Solo alojamiento',
        'breakfast' => 'Desayuno incluido',
        'half_board' => 'Media pensión',
        'full_board' => 'Pensión completa',
        'all_inclusive' => 'Todo incluido',
        'unknown' => 'Régimen por confirmar',
    ],
    'flights' => [
        'title' => 'Buscar vuelos',
        'iata_hint' => 'Código IATA de 3 letras (BOG, CTG, MDE).',
        'outbound' => 'Ida',
        'inbound' => 'Regreso',
        'stops' => '{0} Directo|{1} :count escala|[2,*] :count escalas',
        'fields' => [
            'origin' => 'Origen',
            'destination' => 'Destino',
            'departure_date' => 'Fecha de ida',
            'return_date' => 'Fecha de regreso',
            'ages' => 'Edades de los pasajeros',
            'cabin' => 'Cabina',
        ],
    ],
    'hotels' => [
        'title' => 'Buscar hoteles',
        'stars' => '{1} :count estrella|[2,*] :count estrellas',
        'nights' => '{1} :count noche|[2,*] :count noches',
        'free_cancellation' => 'Cancelación gratis hasta el :date',
        'fields' => [
            'city' => 'Ciudad',
            'country' => 'País (ISO)',
            'check_in' => 'Entrada',
            'check_out' => 'Salida',
            'ages' => 'Edades de los huéspedes',
        ],
    ],
    'fake' => [
        'room' => 'Habitación para :guests',
    ],
    'errors' => [
        'provider_unavailable' => 'El proveedor :provider no está disponible en este momento.',
    ],
];
