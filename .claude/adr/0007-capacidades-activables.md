# ADR-0007: Capacidades de negocio activables e independientes

- **Estado:** Aceptado (2026-10-08). Complementa ADR-0001 y ADR-0002.
- **Contexto:** La agencia necesita encender o apagar funcionalidades principales (cotizaciones, reservas, contabilidad…) por configuración de despliegue, sin dañar el sistema ni perder información. Hoy los 22 módulos se cargan siempre y forman una cadena de dependencias (Finance → Bookings/Payments, Payments → Finance, Reports lee tablas de Finance/Invoicing/Payments), así que apagar uno rompe a otros.

## Decisión

### 1. Núcleo (siempre encendido)
`Shared`, `Organization` (agencia, sucursales, ajustes, festivos), `Identity` (usuarios, roles, alcance), `Audit`, `Workflow` (aprobaciones, tareas), `Pricing` (monedas, tasas, impuestos, reglas de precio), `Suppliers` (maestro de proveedores), `Documents` (PDF), **maestro de clientes y viajeros** (se separa de `Crm`) y la infraestructura de eventos e integraciones.

### 2. Capacidades activables
| Capacidad (`Capability`) | Módulos | Entrada manual cuando falta el flujo previo |
|---|---|---|
| `commercial` Comercial | Crm (prospectos, embudo) | — (es el inicio) |
| `quoting` Cotizaciones | Quotes, Search | Cotización sin prospecto |
| `own_product` Producto propio | Catalog | — |
| `bookings` Reservas | Bookings | Expediente directo, sin cotización |
| `operations` Operación en destino | Operations | Salida con pasajeros cargados a mano |
| `collections` Cobros | Payments | Abono sin expediente (anticipo, venta externa) |
| `accounting` Contabilidad y finanzas | Finance | Venta, gasto y cuenta por pagar manuales |
| `invoicing` Facturación | Invoicing | Factura a un cliente sin expediente |
| `messaging` Mensajería | Communications | — |
| `portals` Portales | Portal | — (requiere `bookings`) |
| `compliance` Cumplimiento | Compliance | — |

`Reports` deja de existir como módulo que lee a todos: cada capacidad publica sus **widgets y reportes** a un tablero del núcleo, que muestra solo los de capacidades encendidas.

### 3. Reglas de independencia
- Una capacidad **nunca** lee tablas ni llama Actions de otra. Solo conoce el núcleo.
- Para **reconocer** lo que hicieron otras capacidades, consume **eventos de integración** y guarda su propia copia (modelo de lectura). Ej.: Contabilidad registra la venta al recibir `BookingConfirmed`, sin consultar Bookings.
- Toda consulta síncrona a otra capacidad pasa por un `Contract` con **implementación nula** registrada cuando la otra está apagada (p. ej. `CashRegister` → `NullCashRegister`).
- Dependencias duras solo cuando no tienen sentido sin la otra (`portals` → `bookings`); se declaran en `Capability::requires()` y se validan al arrancar.

### 4. Cero pérdida de información
- **Bandeja de salida persistente** (`integration_events`): el emisor guarda cada evento de integración en la misma transacción que su cambio, aunque nadie lo escuche.
- Cada capacidad consumidora lleva un **cursor** (`capability_cursors`). Al reencenderse, procesa en orden los eventos ocurridos mientras estuvo apagada (idempotente por `event_id`). Así Contabilidad reconoce las ventas hechas durante el apagado.
- Apagar **no** borra ni oculta tablas: migraciones y modelos se cargan siempre; solo se desactivan rutas, menú, Livewire, listeners, jobs y tareas programadas.

### 5. Encendido por despliegue
`config/capabilities.php` lee `CAPABILITY_<NOMBRE>_ENABLED` del `.env`. Hay un comando `capabilities:status` y una verificación previa al despliegue que falla si hay dependencias incumplidas. Una capacidad apagada responde **404** en web y API.

## Consecuencias
- Refactor por fases. Cada fase deja el sistema verde y entregable.
- Hay datos duplicados a propósito (modelos de lectura por capacidad). La fuente de verdad sigue siendo el emisor.
- Arch tests nuevos: capacidad → solo núcleo + `Contracts`/`Events`. Además hay una matriz de tests que enciende cada capacidad **sola** sobre el núcleo y corre su flujo completo.
- `travel-domain/references/modules.md` y la skill `modular-architecture` se actualizan.
