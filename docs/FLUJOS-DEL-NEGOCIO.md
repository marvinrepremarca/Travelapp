# Flujos del negocio

Cómo trabaja la agencia en el sistema, de punta a punta. Cada paso indica la capacidad que lo habilita ([CAPACIDADES.md](CAPACIDADES.md)).

## 1. Puesta en marcha (una sola vez)

En el orden del menú **Configurar la agencia**: perfil de la agencia → sucursales → usuarios → roles y permisos → parámetros → festivos → cuentas bancarias. Luego **Producto**: proveedores → tasas de cambio → reglas de precio → catálogo propio.

## 2. Del interesado a la venta

```
WhatsApp / web / asesor ─► Lead ─► Cotización ─► Aceptación ─► Expediente ─► Cobro ─► Factura
     (Mensajería)       (Comercial) (Cotizaciones)              (Reservas)   (Cobros) (Facturación)
```

### Lead (Comercial)
Estados: **Nuevo → Contactado → Cotizado → Ganado / Perdido** (un perdido se puede reabrir a Contactado).
- Pasa a *Contactado* al registrar el primer contacto.
- Pasa a *Cotizado* **solo** cuando se envía al cliente una cotización creada desde el lead (no se puede marcar a mano).
- *Ganado* exige el cliente que compra; *Perdido* exige el motivo.
- Con Cotizaciones apagada, un lead contactado se puede ganar directamente.

### Cliente vs. prospecto
Un lead es un **prospecto**: no se registra como cliente automáticamente. El cliente se registra cuando compra.
- **Crear cotización para este lead** abre la cotización con los datos del lead (nombre, correo, teléfono) sin crear el cliente.
- Para crear el expediente, la cotización necesita un cliente: en la cotización, **Asignar cliente** permite elegir uno o registrarlo con los datos del prospecto.

### Cotización (Cotizaciones)
Estados: **Borrador → Enviada → Aceptada / Vencida / Cancelada**.
- Varias opciones (A, B, C…; máximo `TRAVEL_QUOTE_MAX_OPTIONS`, 5 por defecto) con servicios del catálogo, de proveedor o manuales. El precio de venta lo calcula el servidor con las reglas de precio.
- **Enviar** congela una versión y genera el enlace público para el cliente (a clientes registrados también se avisa por WhatsApp).
- El cliente acepta desde el enlace, o el asesor registra la aceptación.
- Vence sola al pasar la vigencia (`TRAVEL_QUOTE_VALIDITY_HOURS`).

### Expediente (Reservas)
Se crea desde la cotización aceptada o como **expediente directo** (sin cotización). Cada servicio tiene estado con el proveedor:

| Estado | Siguiente |
|---|---|
| Por solicitar | En espera, Confirmado, Rechazado, Cancelado |
| En espera del proveedor | Confirmado, Rechazado, Cancelado |
| Confirmado | Cancelado |
| Rechazado | Cancelado |
| Cancelado | — |

Se cambia con el icono ⚙ **Gestionar** del servicio: confirmar pide el código de confirmación del proveedor (y la salida si es producto propio); rechazar o cancelar pide el motivo.
En un servicio directo, elegir un **producto del catálogo** llena los datos y **Calcular neto** toma el costo de la tarifa de la temporada.

### Cobros, finanzas y facturación
- **Cobros**: abonos en efectivo, transferencia (requiere validación de Finanzas) o link de pago; abonos sin expediente para anticipos.
- **Contabilidad**: al confirmarse un servicio se reconoce el ingreso (base de causación) y la cuenta por pagar al proveedor; caja, conciliación bancaria y rentabilidad.
- **Facturación**: factura del expediente o manual; notas crédito (requieren aprobación) y débito.

## 3. Aprobaciones (núcleo)

Las acciones sensibles quedan en espera hasta que alguien con el permiso las apruebe en `/workflow/approvals`. Nadie aprueba su propia solicitud y todo queda auditado.

| Acción que pide aprobación | Quién aprueba por defecto |
|---|---|
| Reembolso | Finanzas (y Gerente) |
| Nota crédito / anulación de factura | Finanzas (y Gerente) |

Tipos definidos pero aún no conectados: descuentos, cambio de precio confirmado y exportación de datos personales.

## 4. Producto propio y operación

- **Catálogo**: productos con temporadas (netos por adulto, niño e infante) y salidas con cupo. Las temporadas no se cruzan; las listas están paginadas y las temporadas vencidas se ocultan.
- Se corrigen con **Editar**: el cupo de una salida no puede quedar por debajo de lo vendido y su fecha/hora solo cambia si no tiene pasajeros. Lo ya cotizado conserva su precio.
- **Operación**: salidas del día con manifiesto, guías, vehículos, incidentes y cierre.

## 5. Mensajería y simulador de WhatsApp

- La **bandeja** (`/communications/conversations`) recibe los mensajes; el bot atiende y el asesor toma la conversación, crea el lead o el cliente y cotiza.
- **Simulador** (`/integrations/whatsapp-simulator`): página pública, sin sesión, para escribir como si fueras un interesado. Limitado por minuto. **Apagar en producción** (`TRAVEL_WHATSAPP_SIMULATOR=false`).

## 6. Proveedores externos

Hoy todos los proveedores son **simulados** (vuelos, hoteles, pagos, WhatsApp, facturación electrónica) detrás de puertos; cambiar a uno real (p. ej. Amadeus) es configurar su adaptador en el `.env` sin tocar el negocio ([ADR-0003](../.claude/adr/0003-integraciones-puertos-adaptadores.md), [ADR-0006](../.claude/adr/0006-proveedores-de-prueba-y-cambio-de-proveedor.md)).
