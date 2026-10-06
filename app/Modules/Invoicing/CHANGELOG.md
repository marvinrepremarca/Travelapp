# Changelog — Invoicing

## Fase 3.3 (parte A: facturas)

### Agregado
- **Factura del expediente** confirmado y pagado en su totalidad (lista "Listos para facturar"); una sola por expediente.
- **Mandato + ingreso propio** desde el precio congelado de cada servicio: el neto del proveedor como *recaudo para terceros* (sin IVA de la agencia) y markup/fees como *ingreso propio* con su IVA; el producto propio es todo ingreso propio; las penalidades de servicios cancelados se recaudan para el proveedor.
- **Consecutivo interno sin saltos** por tipo de documento con prefijo ⚙ (bloqueo de fila dentro de la transacción).
- Datos del cliente congelados al emitir (documento cifrado y enmascarado). Documento inmutable y auditado.
- Puerto **`EInvoicingProvider`** con adaptador Null (Integrations); el envío va en cola después de confirmar la transacción.
