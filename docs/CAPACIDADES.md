# Capacidades del sistema: activar y desactivar módulos

TravelApp está organizado en **capacidades de negocio** que se encienden o apagan por separado (decisión de arquitectura [ADR-0007](../.claude/adr/0007-capacidades-activables.md)). Apagar una capacidad oculta sus pantallas, su menú y sus tareas automáticas **sin afectar a las demás y sin borrar información**.

## 1. Núcleo (siempre encendido)

No se puede apagar. Incluye: agencia y sucursales, usuarios, roles y permisos, clientes y viajeros, proveedores, precios y monedas, documentos (PDF), auditoría, tareas y aprobaciones.

## 2. Capacidades activables

| Capacidad | Variable del `.env` | Qué incluye | Requiere |
|---|---|---|---|
| Comercial | `CAPABILITY_COMMERCIAL_ENABLED` | Embudo de leads (prospectos), seguimiento, conversión en cliente | — |
| Cotizaciones | `CAPABILITY_QUOTING_ENABLED` | Búsqueda de vuelos y hoteles, cotizaciones con versiones, envío y aceptación | — |
| Producto propio | `CAPABILITY_OWN_PRODUCT_ENABLED` | Catálogo de tours, pasadías y paquetes con temporadas, tarifas y cupos | — |
| Reservas | `CAPABILITY_BOOKINGS_ENABLED` | Expedientes, servicios con el proveedor, pasajeros, vouchers e itinerario | — |
| Operación en destino | `CAPABILITY_OPERATIONS_ENABLED` | Salidas, manifiestos, guías y vehículos | Producto propio y Reservas |
| Cobros | `CAPABILITY_COLLECTIONS_ENABLED` | Abonos, links de pago, transferencias, reembolsos y saldos | — |
| Contabilidad y finanzas | `CAPABILITY_ACCOUNTING_ENABLED` | Caja, cuentas por pagar, conciliación bancaria, ingresos y rentabilidad | — |
| Facturación | `CAPABILITY_INVOICING_ENABLED` | Facturas y notas crédito/débito, listas para facturación electrónica | — |
| Mensajería | `CAPABILITY_MESSAGING_ENABLED` | Bandeja de WhatsApp, avisos automáticos al cliente y simulador | — |
| Portales | `CAPABILITY_PORTALS_ENABLED` | Portal del viajero ("Mi viaje") y tienda B2C | Reservas |
| Cumplimiento | `CAPABILITY_COMPLIANCE_ENABLED` | Obligaciones legales, documentos de la agencia y solicitudes de datos personales | — |

Valores: `true` (encendida, por defecto) o `false` (apagada).

## 3. Cómo apagar o encender una capacidad

1. Edita el `.env` del servidor. Ejemplo, operar sin contabilidad:
   ```dotenv
   CAPABILITY_ACCOUNTING_ENABLED=false
   ```
2. Comprueba que la combinación es válida:
   ```bash
   php artisan capabilities:status
   ```
   Muestra cada capacidad, su estado y sus dependencias. Si una capacidad encendida necesita otra apagada (p. ej. Portales sin Reservas), la aplicación **no arranca** y el comando indica qué falta.
3. Aplica la configuración:
   ```bash
   php artisan config:clear      # o php artisan optimize en producción
   ```
4. Verifica en la página de inicio (usuario Administrador del sistema o Gerente): la sección **Capacidades del sistema** muestra el estado actual.

No hace falta migrar ni reiniciar la base de datos.

## 4. Qué pasa al apagar una capacidad

- Sus pantallas responden **404** y desaparecen del menú y de los accesos directos.
- Sus tareas programadas y listeners dejan de ejecutarse.
- Las demás capacidades siguen funcionando con **entrada manual**:

| Si apagas… | Las demás hacen… |
|---|---|
| Comercial | Los clientes se registran a mano; las cotizaciones se hacen sin lead |
| Cotizaciones | Se crean expedientes directos; un lead contactado se puede ganar sin pasar por "Cotizado" |
| Reservas | Cobros registra abonos sin expediente (anticipos, ventas externas); Facturación emite facturas manuales |
| Contabilidad | Las ventas se siguen registrando; Contabilidad las reconocerá al reencenderla |
| Producto propio | Las cotizaciones y servicios se cargan como servicios de proveedor o manuales |
| Mensajería | No salen avisos automáticos; el asesor comparte los enlaces a mano |

## 5. Cero pérdida de información

- Apagar **no borra ni oculta tablas**: los datos siguen ahí.
- Cada evento de negocio (cotización enviada, servicio confirmado, pago recibido…) se guarda en una **bandeja de salida persistente** aunque nadie lo escuche.
- Al **reencender** una capacidad, la tarea `capabilities:catch-up` (cada 5 minutos) procesa en orden lo ocurrido mientras estuvo apagada. Ejemplo: al reencender Contabilidad, registra las ventas confirmadas durante el apagado.
- Algunos eventos no se reprocesan a propósito (p. ej. avisos de WhatsApp viejos: un aviso tardío confunde al cliente).

Para forzar el procesamiento sin esperar:
```bash
php artisan capabilities:catch-up
```

Parámetros de la tarea: `CAPABILITY_CATCH_UP_CRON` (frecuencia), `CAPABILITY_CATCH_UP_CHUNK` (eventos por lote) y `CAPABILITY_CATCH_UP_LOCK_SECONDS`.

## 6. Combinaciones típicas

| Agencia | Capacidades encendidas |
|---|---|
| Solo cotiza y vende con reservas | Comercial, Cotizaciones, Reservas, Cobros |
| Operador de tours propio | Producto propio, Reservas, Operación, Cobros, Portales |
| Completa | Todas |
