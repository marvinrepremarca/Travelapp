<?php

declare(strict_types=1);

return [
    'title' => 'Pagos del expediente :number',
    'link' => 'Pagos',
    'view_booking' => 'Ver expediente',
    'link_description' => 'Pago del expediente :number',
    'method' => [
        'online_link' => 'Link de pago en línea',
        'bank_transfer' => 'Transferencia o consignación',
        'cash' => 'Efectivo en la sucursal',
    ],
    'status' => [
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'expired' => 'Vencido',
    ],
    'fields' => [
        'method' => 'Medio de pago',
        'amount' => 'Valor',
        'reference' => 'Número de comprobante',
        'note' => 'Nota',
    ],
    'summary' => [
        'total' => 'Total del viaje',
        'paid' => 'Pagado',
        'pending' => 'Por confirmar',
        'balance' => 'Saldo',
        'due' => 'El saldo debe estar pago a más tardar el :date.',
        'overdue' => 'Saldo vencido desde el :date.',
    ],
    'register' => [
        'title' => 'Registrar abono',
        'save' => 'Registrar abono',
        'create_link' => 'Generar link de pago',
        'max' => 'Máximo :amount (saldo menos lo pendiente de confirmar).',
        'nothing_to_collect' => 'No hay saldo por cobrar.',
    ],
    'list' => [
        'title' => 'Movimientos',
        'empty' => 'Aún no hay abonos.',
        'reference' => 'Comprobante :reference',
        'link' => 'Link de pago',
        'link_expires' => 'El link vence el :date.',
        'approve' => 'Validar',
        'reject' => 'Rechazar',
        'confirm_reject' => '¿Rechazar la transferencia? El abono no contará en el saldo.',
    ],
    'errors' => [
        'exceeds_balance' => 'El valor supera lo que se puede cobrar (:balance).',
        'not_pending' => 'El pago ya fue resuelto.',
        'invalid_signature' => 'Firma del webhook inválida.',
        'malformed_webhook' => 'Webhook con formato inválido.',
        'unknown_gateway' => 'Pasarela :gateway no configurada.',
        'nothing_to_collect' => 'No hay saldo por cobrar.',
    ],
];
