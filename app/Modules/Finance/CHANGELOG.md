# Changelog — Finance

## Fase 3.2 (parte A: cuentas por pagar)

### Agregado
- **Cuentas por pagar a proveedores** que nacen solas al confirmar un servicio con proveedor (evento `BookingItemConfirmed`), por el neto y con vencimiento según sus condiciones: prepago N días ⚙ antes del servicio, crédito N días después.
- Se **anulan** si el servicio se cancela antes de pagarse (evento `BookingItemCancelled`); las pagadas se conservan.
- **Liquidación por proveedor**: finanzas paga varias obligaciones de un proveedor y una moneda con un comprobante. Totales pendientes por proveedor con el próximo vencimiento y filtro de vencidas.
- Contrato `SupplierDirectory::paymentTermsOf` en Suppliers.
