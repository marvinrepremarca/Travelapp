# Ciclos de vida

Implementa cada máquina de estados como enum con `canTransitionTo()` + Action por transición (patrón State cuando haya comportamiento por estado). Toda transición emite evento, queda auditada con actor y motivo, y se prueba con dataset de transiciones válidas **e inválidas**.

## Lead → Cotización

`Lead`: `new → contacted → quoted → won | lost` (motivo de pérdida obligatorio: precio, fecha, competencia, sin respuesta ⚙).

`QuoteVersion`: `draft → sent → viewed → accepted | rejected | expired`.
- Toda edición de una cotización enviada crea **nueva versión**; las anteriores son inmutables.
- `expires_at` ⚙ (default 72 h). Al vencer, los precios de proveedores externos se marcan "por revalidar".
- `accepted` elige **una** opción y dispara `ConvertQuoteToBookingAction` (revalida precio y disponibilidad de cada ítem antes de reservar).

## Ítem de reserva (`BookingItemStatus`)

```
quoted ──► on_request ──► confirmed ──► ticketed/vouchered ──► in_service ──► completed
   │           │              │                 │
   │           ▼              ▼                 ▼
   │       rejected      cancelled (con/ sin penalidad)   changed (crea nuevo ítem enlazado)
   ▼
 held (bloqueo temporal con fecha límite) ──► expired
```

- `held`: reserva sin emitir con fecha límite (típico en aéreo: TTL). Job programado alerta a las 48 h / 24 h / 4 h ⚙ y, si vence, sincroniza con el proveedor y pasa a `expired`.
- `on_request`: proveedor manual o "bajo petición"; requiere confirmación con localizador y SLA de respuesta ⚙.
- `ticketed` (aéreo) / `vouchered` (resto): documento emitido; solo aquí se considera servicio en firme.
- Un **cambio** nunca edita el ítem original: se cancela/reemite y se enlaza (`replaces_item_id`), conservando la historia financiera.

## Expediente (`BookingStatus`) — derivado

| Estado | Condición |
|---|---|
| `draft` | Sin ítems reservados con proveedor |
| `pending` | Al menos un ítem `held`/`on_request` y ninguno rechazado sin resolver |
| `confirmed` | Todos los ítems activos `confirmed`/`ticketed`/`vouchered` |
| `in_progress` | Fecha actual entre el inicio del primer servicio y el fin del último |
| `completed` | Todos los servicios terminados; habilita cierre financiero y encuesta |
| `cancelled` | Todos los ítems cancelados |
| `closed` | Completado + saldo cliente = 0 + proveedores liquidados + facturado. Inmutable salvo notas |

Calcúlalo en `BookingStatusResolver` (Service puro) y persístelo para filtrar; recalcula en listeners de eventos de ítem.

## Estado financiero del expediente (independiente del operativo)

`unpaid → partially_paid → paid → overpaid (genera saldo a favor) → refunded/partially_refunded`.
Regla ⚙: no se emite/confirma en firme un servicio no reembolsable sin pago total o autorización de crédito del cliente.

## Pago (`PaymentStatus`)

`pending → authorized → captured → (partially_)refunded`, o `failed`/`expired`/`voided`. Solo los webhooks o la consulta a la pasarela cambian el estado; nunca la redirección del navegador.

## Saga de reserva multi-ítem

`ConfirmBookingSaga` reserva ítem por ítem en orden de riesgo (primero los de disponibilidad más volátil: aéreo, luego hotel, luego el resto).
- Si un paso falla: ejecuta compensaciones (cancelar lo ya reservado **dentro del período sin penalidad**) o, si la compensación tiene costo, deja el expediente `pending` y crea una tarea urgente para un asesor. Nunca decidas automáticamente incurrir en una penalidad.
- Cada paso es un job idempotente con `idempotency_key = booking_item.ulid + operación`.
