---
name: queues-notifications
description: Jobs, colas, eventos, sagas asíncronas, notificaciones multicanal (correo, WhatsApp, SMS, push, in-app) y tareas programadas (plazos de emisión y pago, sincronización de contenido, tasas de cambio, recordatorios). Úsala al crear un job, listener, notificación, plantilla de mensaje o tarea del scheduler.
---

# Colas, notificaciones y scheduler

## Colas (Redis + Horizon; nombres como enum `Queue`)

| Cola | Uso | Prioridad |
|---|---|---|
| `critical` | Pasos de saga de reserva, webhooks de pago, emisión | Alta, workers dedicados |
| `suppliers` | Llamadas a proveedores no interactivas (retrieve, sync de estado) | Media, rate-limited por proveedor |
| `notifications` | Correos, WhatsApp, SMS | Media |
| `documents` | PDFs, exportaciones | Media |
| `sync` | Contenido estático, tasas de cambio, BSP | Baja, nocturna |
| `default` | Resto | Media |

## Plantilla de job
```php
final class ConfirmBookingItemWithSupplierJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 60;
    public bool $failOnTimeout = true;

    public function __construct(public readonly string $bookingItemUlid) { $this->onQueue(Queue::Critical->value); }

    public function uniqueId(): string { return $this->bookingItemUlid; }

    /** @return list<int> */
    public function backoff(): array { return [10, 30, 120, 600]; }

    /** @return list<object> */
    public function middleware(): array { return [new WithoutOverlapping($this->bookingItemUlid), new RateLimited('supplier')]; }

    public function handle(ConfirmBookingItemAction $confirm): void { /* delega en Action idempotente */ }

    public function failed(Throwable $e): void { /* marcar on_request + tarea urgente + alerta */ }
}
```
Reglas: payload mínimo (ULIDs, no modelos pesados ni datos sensibles), jobs idempotentes, `after_commit` activo, `failed()` siempre con acción de negocio, nunca `sleep`.

## Notificaciones
- Canales por preferencia del destinatario y tipo de mensaje: correo (siempre), WhatsApp (con opt-in y plantilla aprobada), SMS (urgente), in-app/push (portal).
- Plantillas editables por idioma con variables tipadas (no Blade libre editable por usuarios; usa un motor seguro de sustitución).
- Todo mensaje enviado se registra en el expediente (`Communications`) con estado de entrega.
- Mensajes clave: cotización enviada, reserva confirmada (con voucher/itinerario), recordatorio de pago y de saldo, plazo de emisión (interno), cambio de vuelo, pre-viaje (72 h: documentos, check-in, clima, contactos), durante el viaje (recogida), post-viaje (encuesta), cumpleaños/aniversario de viaje (marketing con consentimiento).

## Scheduler (`routes/console.php`)
| Tarea | Frecuencia ⚙ |
|---|---|
| Alertas de plazos (emisión, pago proveedor, cancelación gratuita, release de cupos) | Cada 15 min |
| Expirar reservas `held` vencidas (sincronizando con el proveedor) | Cada 15 min |
| Recordatorios de cuotas y saldos del cliente | Diario 9:00 hora agencia |
| Tasas de cambio (TRM, etc.) | Diario |
| Sincronización de contenido estático de proveedores | Nocturna |
| Monitor de precios (rebooking) | Cada 6 h |
| Cierre operativo de salidas y manifiestos | Diario |
| Purga/anonimización por retención | Semanal |
| Transiciones `confirmed → in_progress → completed` | Horaria |

Todas con `->onOneServer()->withoutOverlapping()` y monitoreadas (heartbeat); respetan la zona horaria de la agencia.

## Tests
`Queue::fake()` para verificar despacho; tests de `handle()` con fakes HTTP; `Notification::fake()` con assert de canal y contenido; tests del scheduler con `travelTo`.

## Checklist de cumplimiento
- [ ] Cola asignada con enum `Queue`; `tries`, `timeout` y `backoff` desde configuración o constantes con nombre.
- [ ] Job idempotente, payload mínimo sin datos sensibles, `failed()` con acción de negocio.
- [ ] Eventos despachados después del commit.
- [ ] Notificación con plantilla traducible, canal según preferencia y opt-in; registrada en el expediente.
- [ ] Tareas programadas con `onOneServer()->withoutOverlapping()`, heartbeat y zona horaria de la agencia.
- [ ] Tests con `Queue::fake()`, `Notification::fake()` y `travelTo`.
