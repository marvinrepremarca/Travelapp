<?php

declare(strict_types=1);

return [
    'payable_status' => [
        'open' => 'Pendiente',
        'paid' => 'Pagada',
        'voided' => 'Anulada',
    ],
    'payables' => [
        'title' => 'Cuentas por pagar a proveedores',
        'by_supplier' => 'Pendiente por proveedor',
        'nothing_open' => 'No hay obligaciones pendientes con proveedores.',
        'items' => '{1} :count servicio|[2,*] :count servicios',
        'next_due' => 'próximo vencimiento :date',
        'supplier' => 'Proveedor',
        'all_suppliers' => 'Todos los proveedores',
        'status' => 'Estado',
        'all_statuses' => 'Todos los estados',
        'only_overdue' => 'Solo vencidas',
        'empty' => 'No hay cuentas por pagar con estos filtros.',
        'service' => 'Servicio',
        'due' => 'Vence',
        'amount' => 'Neto',
        'selection' => 'Obligaciones a pagar',
        'select' => 'Seleccionar :service',
        'payment_reference' => 'Comprobante de la transferencia',
        'settle_hint' => 'Selecciona obligaciones de un mismo proveedor y moneda.',
        'settle' => 'Registrar pago (liquidación)',
        'settled' => 'Liquidación registrada por :amount.',
    ],
    'errors' => [
        'payables_not_payable' => 'Alguna obligación ya no está pendiente; actualiza la lista.',
        'mixed_settlement' => 'Una liquidación debe ser de un solo proveedor y una sola moneda.',
        'cash_session_already_open' => 'La caja de esta sucursal ya está abierta.',
        'cash_session_closed' => 'La caja está cerrada.',
    ],
];
