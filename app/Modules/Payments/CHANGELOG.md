# Changelog — Payments

## Fase 3.1 (parte A: abonos, links de pago y webhooks)

### Agregado
- **Estado de cuenta del expediente**: total vigente, pagado, por confirmar y saldo en la moneda de venta, con **fecha límite** del saldo (N días ⚙ antes del primer servicio) y aviso de vencido.
- **Abonos libres** por transferencia (pendiente hasta que **finanzas** la valide) o efectivo (aprobado). Nunca se cobra más del saldo menos lo pendiente.
- **Links de pago** de la pasarela activa (`TRAVEL_PAYMENT_GATEWAY`; hoy la simulada, PCI DSS SAQ-A: nunca datos de tarjeta), con vencimiento automático.
- **Webhooks firmados** (HMAC-SHA256), idempotentes por id de evento y procesados en la cola `payments`; un pago resuelto no cambia por eventos tardíos.
- Los pagos aprobados son inmutables: no se borran ni cambian de monto.

## Fase 3.1 (parte B: reembolsos)

### Agregado
- El estado de cuenta incluye **penalidades** (lo que el cliente debe tras cancelar) y **reembolsos**; un saldo negativo es saldo a favor del cliente.
- **Reembolsos**: el asesor solicita hasta lo pagado de más (descontando servicios vigentes, penalidades y reembolsos en curso); la solicitud crea una **aprobación de finanzas** en Workflow y la decisión llega por el evento `ApprovalResolved`. Finanzas registra el pago del reembolso con su comprobante. Los reembolsos no se borran y quedan auditados.

## 2026-10-25
- Abonos de clientes sin expediente (ADR-0007): anticipos y ventas externas en efectivo o transferencia; pantalla Abonos de clientes; funciona con Reservas apagada.
