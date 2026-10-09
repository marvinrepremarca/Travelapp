# Changelog — Invoicing

## Fase 3.3 (parte A: facturas)

### Agregado
- **Factura del expediente** confirmado y pagado en su totalidad (lista "Listos para facturar"); una sola por expediente.
- **Mandato + ingreso propio** desde el precio congelado de cada servicio: el neto del proveedor como *recaudo para terceros* (sin IVA de la agencia) y markup/fees como *ingreso propio* con su IVA; el producto propio es todo ingreso propio; las penalidades de servicios cancelados se recaudan para el proveedor.
- **Consecutivo interno sin saltos** por tipo de documento con prefijo ⚙ (bloqueo de fila dentro de la transacción).
- Datos del cliente congelados al emitir (documento cifrado y enmascarado). Documento inmutable y auditado.
- Puerto **`EInvoicingProvider`** con adaptador Null (Integrations); el envío va en cola después de confirmar la transacción.

## Fase 3.3 (parte B: notas crédito y débito)

### Agregado
- **Nota crédito** total o parcial por línea, sin acreditar más de lo que queda (cuenta notas emitidas y solicitudes pendientes). El IVA se acredita en proporción y el saldo exacto de la línea toma el IVA restante. Pasa por **aprobación de finanzas** (Workflow, *Nota crédito / anulación de factura*) y se emite sola al aprobarse con consecutivo NC-.
- **Nota débito** con cargos de recaudo para terceros o ingreso propio; el IVA del ingreso propio sale de las reglas vigentes de Pricing (contrato `IncomeTaxes`). Consecutivo ND-.
- La factura muestra sus notas, las solicitudes y el **neto con notas**. Las notas copian los datos congelados del cliente y también se envían al puerto de facturación electrónica.

## 2026-10-24
- Factura manual a un cliente sin expediente (ADR-0007): ingreso propio con IVA y recaudo para terceros; funciona con Reservas apagada.
