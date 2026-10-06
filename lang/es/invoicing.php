<?php

declare(strict_types=1);

return [
    'title' => 'Facturación',
    'type' => [
        'invoice' => 'Factura',
        'credit_note' => 'Nota crédito',
        'debit_note' => 'Nota débito',
    ],
    'line_kind' => [
        'third_party' => 'Recaudo para terceros',
        'own_income' => 'Ingreso propio',
    ],
    'e_invoice_status' => [
        'not_applicable' => 'Factura interna',
        'pending' => 'Enviando',
        'accepted' => 'Aceptada',
        'rejected' => 'Rechazada',
    ],
    'lines' => [
        'third_party' => ':service (recaudo para el proveedor)',
        'own_income' => ':service (servicio de intermediación de la agencia)',
    ],
    'tabs' => [
        'ready' => 'Listos para facturar',
        'issued' => 'Documentos emitidos',
    ],
    'ready_hint' => 'Expedientes confirmados y pagados en su totalidad que aún no tienen factura.',
    'ready_empty' => 'No hay expedientes listos para facturar',
    'ready_empty_hint' => 'Un expediente aparece aquí cuando todos sus servicios están confirmados y el cliente pagó el total.',
    'issued_empty' => 'No hay documentos con estos filtros.',
    'issue' => 'Emitir factura',
    'confirm_issue' => '¿Emitir la factura del expediente :booking? Una factura emitida no se edita; se corrige con notas.',
    'issued' => 'Factura :number emitida.',
    'document_type' => 'Tipo de documento',
    'all_types' => 'Todos los tipos',
    'booking' => 'Expediente',
    'customer' => 'Cliente',
    'number' => 'Número',
    'issued_at' => 'Fecha de emisión',
    'total' => 'Total',
    'e_invoice' => 'Facturación electrónica',
    'show_title' => ':type :number',
    'masked_document' => 'terminado en :last',
    'related' => 'Documento afectado',
    'reason' => 'Motivo',
    'lines_title' => 'Detalle',
    'description' => 'Descripción',
    'kind' => 'Naturaleza',
    'amount' => 'Valor',
    'tax' => 'IVA',
    'totals' => 'Totales',
    'third_party' => 'Recaudo para terceros',
    'own_income' => 'Ingreso propio (base IVA)',
    'back' => 'Volver a facturación',
    'errors' => [
        'booking_not_confirmed' => 'Solo se factura un expediente con todos sus servicios confirmados.',
        'booking_not_paid' => 'El expediente aún tiene un saldo de :balance; se factura cuando el cliente pague el total.',
        'already_invoiced' => 'Este expediente ya tiene factura; para corregirla usa una nota crédito o débito.',
        'nothing_to_invoice' => 'El expediente no tiene valores para facturar.',
    ],
];
