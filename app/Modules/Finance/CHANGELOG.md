# Changelog — Finance

## Fase 3.2 (parte A: cuentas por pagar)

### Agregado
- **Cuentas por pagar a proveedores** que nacen solas al confirmar un servicio con proveedor (evento `BookingItemConfirmed`), por el neto y con vencimiento según sus condiciones: prepago N días ⚙ antes del servicio, crédito N días después.
- Se **anulan** si el servicio se cancela antes de pagarse (evento `BookingItemCancelled`); las pagadas se conservan.
- **Liquidación por proveedor**: finanzas paga varias obligaciones de un proveedor y una moneda con un comprobante. Totales pendientes por proveedor con el próximo vencimiento y filtro de vencidas.
- Contrato `SupplierDirectory::paymentTermsOf` en Suppliers.

## Fase 3.2 (parte B: caja diaria)

### Agregado
- **Caja por sucursal**: apertura con base (una sola abierta por sucursal), entradas automáticas por **abonos en efectivo** (contrato `CashRegister`; sin caja abierta no se recibe efectivo), salidas con descripción y **cierre con arqueo** que registra esperado, contado y diferencia (sobrante o faltante).
- Movimientos y cajas inmutables y auditados; historial de cierres. Quien tiene alcance total puede operar la caja de cualquier sucursal.

## Fase 3.2 (parte C: rentabilidad por expediente)

### Agregado
- **Rentabilidad** (menú 5.5, permiso de ver márgenes): por expediente vendido en el mes, venta vigente, costo (neto con la tasa congelada al cotizar), margen y % sobre la venta, **comisión esperada** del proveedor (pactada a la fecha de venta, sobre tarifa pública o neto), **penalidades** y utilidad.
- Totales **por asesor**, **por sucursal** y del período, separados por moneda y filtrados por el alcance de quien consulta. Los servicios cancelados o rechazados solo aportan sus penalidades.
- Contrato `BookingProfitLines` en Bookings (una consulta agrupada) e índice `bookings.created_at`.
