<?php

declare(strict_types=1);

return [
    'home' => 'Inicio y guía de puesta en marcha',
    'step' => 'Paso :number',
    'stages' => [
        'setup' => [
            'title' => 'Configurar la agencia',
            'description' => 'Lo primero, una sola vez (gerencia o administración): sin esto no se puede trabajar.',
        ],
        'products' => [
            'title' => 'Proveedores y precios',
            'description' => 'Con quién se compra y a cuánto se vende. Requiere la etapa 1.',
        ],
        'sales' => [
            'title' => 'Vender',
            'description' => 'Clientes, búsqueda y cotizaciones. Requiere las etapas 1 y 2.',
        ],
        'operations' => [
            'title' => 'Reservar, cobrar y operar',
            'description' => 'Expedientes que nacen de una cotización aceptada: confirmación, pasajeros, pagos y documentos.',
        ],
        'control' => [
            'title' => 'Seguimiento y control',
            'description' => 'Se usa todos los días en paralelo a las demás etapas.',
        ],
    ],
    'items' => [
        'agency' => ['label' => 'Datos de la agencia', 'hint' => 'NIT, RNT, contacto, logo y colores. Aparecen en cotizaciones y vouchers.'],
        'branches' => ['label' => 'Sucursales', 'hint' => 'Oficinas de venta. Cada usuario y cada venta pertenece a una.'],
        'users' => ['label' => 'Usuarios y roles', 'hint' => 'Asesores, finanzas, producto… El rol define qué ve y qué puede hacer cada uno.'],
        'settings' => ['label' => 'Parámetros', 'hint' => 'Vigencia de cotizaciones, moneda, zona horaria, visibilidad de márgenes, spread de cambio.'],
        'holidays' => ['label' => 'Festivos', 'hint' => 'Calendario para plazos y vencimientos.'],
        'suppliers' => ['label' => 'Proveedores', 'hint' => 'Hoteles, operadores y transportadores con RNT y condiciones de pago.'],
        'rates' => ['label' => 'Tasas de cambio', 'hint' => 'TRM del día (USD→COP). Sin tasa no se puede vender en otra moneda.'],
        'pricing_rules' => ['label' => 'Reglas de precio', 'hint' => 'Markup, fees e IVA que convierten el costo del proveedor en precio de venta.'],
        'catalog' => ['label' => 'Catálogo propio', 'hint' => 'Tours, pasadías, traslados y paquetes propios, con temporadas, tarifas y salidas con cupo.'],
        'simulator' => ['label' => 'Simulador de precios', 'hint' => 'Prueba cuánto costaría un servicio con las reglas de precio vigentes.'],
        'customers' => ['label' => 'Clientes y viajeros', 'hint' => 'Quién compra y quién viaja (con consentimiento de datos). Necesario para cotizar.'],
        'leads' => ['label' => 'Oportunidades', 'hint' => 'Embudo de ventas: solicitudes de viaje antes de cotizar.'],
        'flights' => ['label' => 'Buscar vuelos', 'hint' => 'Busca en los proveedores conectados y agrega la oferta a una cotización.'],
        'hotels' => ['label' => 'Buscar hoteles', 'hint' => 'Busca en los proveedores conectados y agrega la oferta a una cotización.'],
        'quotes' => ['label' => 'Cotizaciones', 'hint' => 'Opciones para el cliente; se envían, el cliente las acepta y se convierten en expediente.'],
        'bookings' => ['label' => 'Expedientes', 'hint' => 'Confirmar servicios, asignar pasajeros, cobrar (Pagos), cancelar y descargar vouchers.'],
        'payables' => ['label' => 'Cuentas por pagar', 'hint' => 'Lo que se debe a cada proveedor por servicios confirmados; liquidación con comprobante (finanzas).'],
        'bank_accounts' => ['label' => 'Cuentas bancarias', 'hint' => 'Cuentas de la agencia y el formato del extracto CSV de cada banco (finanzas).'],
        'reconciliation' => ['label' => 'Conciliación bancaria', 'hint' => 'Carga el extracto del banco y cruza cada movimiento con abonos, pagos a proveedores y consignaciones.'],
        'cash' => ['label' => 'Caja de la sucursal', 'hint' => 'Abre la caja cada día con la base; los abonos en efectivo entran solos; cierra con el arqueo.'],
        'tasks' => ['label' => 'Tareas', 'hint' => 'Pendientes y recordatorios del equipo.'],
        'approvals' => ['label' => 'Aprobaciones', 'hint' => 'Descuentos, reembolsos y otras decisiones que requieren visto bueno.'],
        'audit' => ['label' => 'Auditoría', 'hint' => 'Quién cambió qué y cuándo.'],
        'profitability' => ['label' => 'Rentabilidad', 'hint' => 'Venta, costo, margen, comisiones y penalidades por expediente, asesor y sucursal.'],
        'security' => ['label' => 'Mi seguridad', 'hint' => 'Tu contraseña y verificación en dos pasos.'],
    ],
    'guide' => [
        'title' => 'Guía de puesta en marcha',
        'intro' => 'Sigue los pasos en orden: cada etapa necesita las anteriores. Solo ves los pasos que tu rol puede hacer.',
        'open' => 'Abrir',
        'flow_title' => 'Flujo de una venta',
        'flow' => 'Cliente (3.1) → buscar o elegir del catálogo (3.3, 3.4, 2.4) → cotización (3.5) → el cliente acepta → expediente (4.1) → confirmar servicios y pasajeros → pagos → vouchers.',
    ],
];
