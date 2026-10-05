<?php

declare(strict_types=1);

return [
    'fake_checkout' => [
        'title' => 'Pago en línea (pasarela simulada)',
        'notice' => 'Entorno de prueba: aquí no se ingresan datos de tarjeta. Elige el resultado del pago.',
        'amount' => 'Valor a pagar: :amount',
        'reference' => 'Referencia :reference',
        'approve' => 'Simular pago aprobado',
        'reject' => 'Simular pago rechazado',
        'done' => 'Resultado enviado a la agencia: :status.',
    ],
    'errors' => [
        'request_failed' => 'El servicio externo :provider no respondió correctamente.',
        'host_not_allowed' => 'El host :host no está autorizado para llamadas externas.',
    ],
];
