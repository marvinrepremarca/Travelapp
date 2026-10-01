<?php

declare(strict_types=1);

return [
    'official_rate_saved' => 'Tasa oficial guardada para :count día(s): :rate',
    'rate_source' => [
        'official' => 'Oficial (TRM)',
        'manual' => 'Manual',
    ],
    'rates' => [
        'title' => 'Tasas de cambio',
        'record_title' => 'Registrar tasa',
        'record' => 'Guardar tasa',
        'fetch_official' => 'Descargar TRM de la fecha',
        'saved' => 'La tasa se guardó.',
        'fetched' => 'La TRM se descargó y se guardó.',
        'pair' => 'Par',
        'source' => 'Fuente',
        'rate_hint' => '1 unidad de la moneda base en la moneda destino. La tasa de la agencia suma el spread configurado.',
        'empty_title' => 'Sin tasas registradas',
        'empty_description' => 'Descarga la TRM o registra una tasa manual.',
        'fields' => [
            'base' => 'moneda base',
            'quote' => 'moneda destino',
            'rate' => 'tasa',
            'valid_on' => 'fecha',
        ],
    ],
    'errors' => [
        'rate_unavailable' => 'No hay tasa :from → :to para el :date. Regístrala en Tasas de cambio.',
        'source_failed' => 'La fuente oficial de la TRM no está disponible. Intenta más tarde o registra la tasa manualmente.',
        'invalid_rate' => 'La tasa debe ser positiva y entre monedas distintas.',
    ],
];
