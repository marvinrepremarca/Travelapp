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
    'shop' => [
        'title' => 'Tours y experiencias',
        'hint' => 'Elige tu experiencia y la salida que prefieras. Un asesor confirma cupos y tarifas y te envía la cotización.',
        'empty' => 'Por ahora no hay salidas abiertas. Vuelve pronto.',
        'from' => 'Desde',
        'per_person' => 'por persona',
        'price_on_request' => 'Precio a consultar',
        'departures_count' => '{1} :count salida disponible|[0,*] :count salidas disponibles',
        'see' => 'Ver salidas y reservar',
        'back' => 'Volver a la tienda',
        'request_title' => 'Solicita tu reserva',
        'departure_option' => ':date (:seats cupos)',
        'estimate' => 'Valor estimado para :seats persona(s)',
        'consent' => 'Autorizo el tratamiento de mis datos personales para gestionar esta solicitud, según la política de privacidad (versión :version).',
        'send' => 'Solicitar reserva',
        'disclaimer' => 'El valor es estimado. Los cupos no quedan apartados hasta que un asesor confirme y recibas el link de pago.',
        'requested' => '¡Recibimos tu solicitud! Un asesor te contactará pronto para confirmar cupos y enviarte la cotización.',
        'no_estimate' => 'sin estimado',
        'lead_notes' => "Solicitud desde la tienda en línea.
Producto: :product
Salida: :departure
Cupos: :seats
Valor estimado: :estimate
Consentimiento de tratamiento de datos: sí (política :policy).",
        'fields' => [
            'departure' => 'Salida',
            'seats' => 'Personas',
            'contactName' => 'Nombre completo',
            'email' => 'Correo electrónico',
            'phone' => 'Teléfono / WhatsApp',
            'acceptsDataProcessing' => 'autorización de tratamiento de datos',
        ],
        'errors' => [
            'departure_unavailable' => 'Esa salida ya no está disponible. Elige otra.',
            'not_enough_seats' => 'Solo quedan :available cupos en esa salida.',
            'consent_required' => 'Debes autorizar el tratamiento de tus datos para continuar.',
            'no_advisor' => 'No pudimos registrar tu solicitud en este momento. Intenta más tarde.',
            'too_many' => 'Demasiadas solicitudes. Intenta de nuevo en una hora.',
        ],
    ],
];
