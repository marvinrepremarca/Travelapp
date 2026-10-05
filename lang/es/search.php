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
    'target_quote' => 'Agregar a la cotización',
    'no_drafts' => 'No tienes cotizaciones en borrador; crea una para agregar ofertas.',
    'add_to_quote' => 'Agregar a cotización',
    'quote_required' => 'Elige una cotización en borrador y vuelve a intentarlo.',
    'added_to_quote' => 'Oferta agregada a la cotización; su venta la calculan las reglas de precio.',
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
        'place_hint' => '',
        'outbound' => 'Ida',
        'inbound' => 'Regreso',
        'stops' => '{0} Directo|{1} :count escala|[2,*] :count escalas',
        'description_one_way' => 'Vuelo :origin → :destination · :flight',
        'description_round_trip' => 'Vuelo :origin ⇄ :destination · :flight',
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
        'city_hint' => 'Escribe la ciudad; al elegirla de la lista se completa el país.',
        'fields' => [
            'city' => 'Ciudad',
            'country' => 'País',
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
    'places' => [
        'choose_from_list' => 'No reconocemos ese lugar: elígelo de la lista de sugerencias.',
    ],
];
