# 0004. Facturación electrónica preparada, sin integración inicial

- Estado: Aceptado
- Fecha: 2026-09-29

## Contexto
La agencia no tiene todavía un proveedor tecnológico de facturación electrónica definido. Aun así, la DIAN la exige y el sistema debe poder conectarse más adelante sin rehacer el módulo `Invoicing`.

## Opciones consideradas
1. **Integrar ya con un proveedor**: sería prematuro, porque no hay proveedor elegido.
2. **Ignorar la facturación electrónica**: obligaría a una migración costosa después.
3. **Diseñar el dominio completo y dejar un puerto sin implementar**: el sistema queda preparado a bajo costo.

## Decisión
Opción 3.
- Puerto `Invoicing/Contracts/EInvoicingProvider` con las operaciones `submit(Invoice)`, `status(reference)`, `submitCreditNote(...)`, `submitDebitNote(...)` y `downloadRepresentation(...)`. Todas devuelven DTOs de dominio.
- Adaptador por defecto: `NullEInvoicingProvider`. No envía nada, registra la factura como `not_submitted` y lo informa en la UI. Se selecciona por configuración (`config/invoicing.php` → `electronic.driver`).
- Desde hoy el modelo guarda lo que exige la factura electrónica:
  - resolución y rango de numeración, con prefijo;
  - datos fiscales completos del adquiriente;
  - impuestos por línea y retenciones;
  - forma y medio de pago;
  - moneda y TRM;
  - campos reservados `electronic_status`, `cufe`, `electronic_submitted_at` y `electronic_response`.
- Al emitirse una factura se dispara el evento `InvoiceIssued`. Cuando haya integración, un listener la enviará en cola, sin cambiar las Actions.
- Las facturas emitidas son inmutables; toda corrección se hace con notas crédito/débito.

## Consecuencias
- Conectar un proveedor será crear su adaptador (comando `/nueva-integracion`) y cambiar la configuración.
- Mientras no haya integración, la agencia debe facturar electrónicamente por fuera. El sistema lo recuerda con un aviso configurable.
