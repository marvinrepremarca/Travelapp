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

## Fase 3.2 (parte D: conciliación bancaria)

### Agregado
- **Cuentas bancarias de la agencia** (crear y editar con formulario validado; solo últimos 4 dígitos) con el **formato del extracto CSV** de cada banco: columnas por título del encabezado, separador, fila del encabezado, formato de fecha, separador decimal y valor con signo o débito/crédito.
- **Carga del extracto** todo-o-nada: el mismo archivo no se carga dos veces y las líneas de extractos que se solapan no se duplican; una fila ilegible detiene la carga indicando el número de fila.
- **Cruce sugerido + confirmación** contra abonos de clientes por transferencia o en línea (contrato `ReceivedPayments` de Payments), **liquidaciones a proveedores** (salida por la suma de la liquidación) y **consignaciones de caja**: valor exacto, fecha dentro de ±N días ⚙ y referencia primero. Cada movimiento del sistema se concilia una sola vez; las líneas sin contrapartida (comisiones, GMF) se ignoran con nota; todo se puede deshacer y queda auditado.
- Movimientos del sistema sin reflejo en el banco del período. Caja: nuevo tipo de salida **Consignación al banco**.

## 2026-10-23
- Ingresos (ADR-0007, base de causación): se reconocen al confirmar cada servicio por su precio de venta y se reversan si se cancela; ventas registradas a mano; pantalla Ingresos conciliada con lo cobrado y lo facturado.
- Cuentas por pagar registradas a mano, sin expediente (columna `source`).
