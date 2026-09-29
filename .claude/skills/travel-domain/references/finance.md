# Finanzas del negocio de viajes

## Cobros al cliente
- Medios: pasarela (tarjeta, PSE, billeteras), link de pago, transferencia (con soporte adjunto y verificación manual), efectivo en sucursal (caja), saldo a favor, cuota corporativa (crédito).
- Planes de pago: abono inicial ⚙ (% o monto) + cuotas con fecha; saldo total antes de X días de la salida ⚙. Recordatorios automáticos y bloqueo de emisión si no se paga (según regla financiera del expediente).
- Pagos divididos: varios pagadores en un mismo expediente (grupos, parejas); cada participante con su link y su saldo.
- Recargo por medio de pago ⚙ (ej. tarjeta de crédito internacional) solo si la ley y la pasarela lo permiten.
- Multi-moneda: se puede cotizar en USD y cobrar en COP a la TRM del día o a una tasa pactada; se registra la diferencia en cambio.

## Pagos a proveedores
- Condiciones por contrato: prepago, crédito a N días, pago en destino por el cliente, BSP (aerolíneas), tarjeta virtual de un solo uso.
- Cuentas por pagar generadas desde los ítems confirmados; conciliación con la factura del proveedor (`SupplierInvoice`) y alertas de diferencias.
- Programación de pagos por fecha de vencimiento; aprobación por rol ⚙ para montos altos.

## BSP / ARC (aéreo)
- La agencia acreditada IATA liquida con aerolíneas vía BSP en ciclos ⚙. El sistema importa el reporte de facturación (HOT/billing analysis) y concilia tiquete por tiquete contra lo emitido (tarifa, impuestos, comisión, ADM/ACM).
- Agencias no IATA emiten a través de un consolidador: se trata como proveedor normal.

## Comisiones
- **Entrantes:** del proveedor a la agencia (hoteles, actividades, aerolíneas con over-commission). Se rastrean como cuenta por cobrar hasta recibirlas.
- **Salientes:** a vendedores internos (esquemas por margen o venta, metas por sucursal), a subagencias B2B y a referidos/afiliados.
- La comisión se calcula sobre la base configurada (neto, venta sin impuestos, margen) y se liquida al cerrar el expediente o al recibir el pago ⚙.

## Rentabilidad
Por expediente e ítem: `venta – neto – costos de pasarela – comisiones pagadas + comisiones recibidas ± diferencia en cambio`. Reportes por asesor, sucursal, producto, proveedor, destino, canal y cliente.

## Facturación
- Dos esquemas que el sistema debe soportar por ítem ⚙:
  - **Intermediación (mandato):** la agencia factura solo su comisión/fee como ingreso propio; el valor del servicio es un ingreso para terceros.
  - **Venta propia:** la agencia factura el total (paquetes propios, producto propio, tarifas netas con markup).
- Facturación electrónica **futura** (ADR-0004): hoy la factura se emite internamente y queda en estado `pending_electronic_submission` cuando la integración esté activa; el modelo ya guarda todo lo que exige la DIAN (ver `regulatory-colombia.md`). Anulaciones solo con nota crédito; nunca se borra ni edita una factura emitida.

## Caja y conciliación
- Caja por sucursal con apertura/cierre y arqueo.
- Conciliación bancaria importando extractos; pagos no identificados en una bandeja.
- Exportación/integración con el ERP contable (plan de cuentas configurable por agencia).

## Reglas de integridad
- Libro de movimientos (`ledger_entries`) de doble partida e inmutable; el saldo se calcula, no se edita.
- Todo reembolso ≤ lo pagado; requiere motivo, aprobación ⚙ y enlace al pago original.
