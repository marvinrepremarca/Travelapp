<?php

declare(strict_types=1);

return [
    'title' => 'Mi viaje :number',
    'greeting' => 'Hola, :name',
    'booking' => 'Expediente :number',
    'nothing_to_pay' => 'No hay saldo pendiente por pagar en línea (puede haber un pago en proceso).',
    'personal_link' => 'Este enlace es personal y tiene vencimiento.',
    'request_new' => 'Pedir un enlace nuevo',
    'statement' => [
        'title' => 'Estado de cuenta',
        'total' => 'Total del viaje',
        'paid' => 'Pagado',
        'balance' => 'Saldo',
        'due' => 'Fecha límite de pago',
        'pay' => 'Pagar :amount en línea',
        'pending' => 'Hay pagos por :amount en proceso de confirmación.',
        'paid_in_full' => '¡Tu viaje está pagado en su totalidad!',
    ],
    'itinerary' => [
        'title' => 'Itinerario',
        'nights' => '{1} :count noche|[2,*] :count noches',
        'code' => 'Confirmación :code',
        'voucher' => 'Descargar voucher',
        'download' => 'Descargar itinerario (PDF)',
    ],
    'access' => [
        'title' => 'Acceder a Mi viaje',
        'hint' => 'Escribe el número de tu expediente y el documento del titular. Te enviaremos el enlace a tu WhatsApp registrado.',
        'booking_number' => 'Número de expediente',
        'booking_hint' => 'Aparece en tus vouchers y en tu itinerario.',
        'document_number' => 'Documento del titular',
        'send' => 'Enviarme el enlace',
        'sent' => 'Si los datos coinciden con un viaje, te enviamos el enlace a tu WhatsApp registrado en unos minutos.',
        'too_many' => 'Demasiados intentos. Intenta de nuevo en una hora.',
    ],
];
