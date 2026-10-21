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

## 3. Dependencias y paquetes

Cada capacidad funciona sola sobre el núcleo, **salvo dos dependencias obligatorias**:

| Capacidad | Requiere obligatoriamente | Por qué |
|---|---|---|
| Operación en destino | Producto propio **y** Reservas | Opera las salidas del catálogo propio con los pasajeros de los expedientes |
| Portales | Reservas | El viajero consulta su expediente; la tienda B2C crea reservas |

Si una capacidad encendida no tiene sus requisitos, la aplicación **no arranca** y `php artisan capabilities:status --check` indica qué falta.

### Afinidades recomendadas (no obligatorias)

Funcionan por separado, pero juntas se reconocen entre sí y evitan registrar a mano:

| Si contratas… | Conviene sumar… | Qué ganas al tenerlas juntas |
|---|---|---|
| Comercial | Cotizaciones | El lead se cotiza con sus datos y pasa a "Cotizado" solo al enviar la cotización |
| Cotizaciones | Reservas | La cotización aceptada se convierte en expediente sin volver a digitar |
| Producto propio | Cotizaciones o Reservas | El neto se calcula con la tarifa de la temporada y se descuenta el cupo |
| Reservas | Cobros | Abonos, links de pago y saldo por expediente |
| Reservas | Contabilidad | Ingresos y cuentas por pagar automáticas al confirmar servicios |
| Reservas | Facturación | Factura del expediente con un clic |
| Cotizaciones o Cobros | Mensajería | Avisos automáticos por WhatsApp (cotización enviada, link de pago, recordatorio de saldo) |

### Paquetes sugeridos

| Paquete | Capacidades | Para quién |
|---|---|---|
| **Básico** (siempre incluido) | Núcleo | Clientes, proveedores, precios, usuarios, auditoría |
| **Ventas** | Comercial + Cotizaciones | Agencia que solo cotiza y hace seguimiento |
| **Ventas y reservas** | Ventas + Reservas + Cobros | Agencia emisora típica |
| **Administrativo** | Contabilidad + Facturación | Se suma a cualquier paquete con Reservas o Cobros |
| **Operador de tours** | Producto propio + Reservas + Operación en destino + Cobros | Receptivo con salidas propias (Operación exige Producto propio y Reservas) |
| **Digital** | Portales + Mensajería | Portal del viajero, tienda B2C y WhatsApp (Portales exige Reservas) |
| **Cumplimiento** | Cumplimiento | Obligaciones legales y protección de datos; se suma a cualquiera |
| **Completo** | Todas | Agencia integral |

## 4. Cómo apagar o encender una capacidad

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

## 5. Qué pasa al apagar una capacidad

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

## 6. Cero pérdida de información

- Apagar **no borra ni oculta tablas**: los datos siguen ahí.
- Cada evento de negocio (cotización enviada, servicio confirmado, pago recibido…) se guarda en una **bandeja de salida persistente** aunque nadie lo escuche.
- Al **reencender** una capacidad, la tarea `capabilities:catch-up` (cada 5 minutos) procesa en orden lo ocurrido mientras estuvo apagada. Ejemplo: al reencender Contabilidad, registra las ventas confirmadas durante el apagado.
- Algunos eventos no se reprocesan a propósito (p. ej. avisos de WhatsApp viejos: un aviso tardío confunde al cliente).

Para forzar el procesamiento sin esperar:
```bash
php artisan capabilities:catch-up
```

Parámetros de la tarea: `CAPABILITY_CATCH_UP_CRON` (frecuencia), `CAPABILITY_CATCH_UP_CHUNK` (eventos por lote) y `CAPABILITY_CATCH_UP_LOCK_SECONDS`.

## 7. Encender una capacidad con fecha de inicio

Por defecto, al encender una capacidad se reprocesa **toda la historia** que no conoce. Si la agencia la contrata tiempo después, puede no querer eso. Ejemplo: empieza a usar Contabilidad el 1 de enero con los saldos iniciales cargados a mano; traer las ventas del año anterior **duplicaría** esas cifras.

Para arrancar desde una fecha, define en el `.env` **antes** de encenderla:

```dotenv
CAPABILITY_ACCOUNTING_ENABLED=true
CAPABILITY_ACCOUNTING_REPLAY_SINCE=2027-01-01
```

- Formato `AAAA-MM-DD`. Se toma el inicio de ese día en la zona horaria de la agencia.
- Solo se reprocesa lo ocurrido **desde** esa fecha; lo anterior nunca se trae a esa capacidad (los datos siguen guardados).
- Vacía = toda la historia (comportamiento por defecto).
- Hay una variable por capacidad: `CAPABILITY_<NOMBRE>_REPLAY_SINCE`.
- Una fecha mal escrita impide el arranque y `capabilities:status --check` la reporta.
- `php artisan capabilities:status` muestra la fecha de inicio de cada capacidad en la columna **Reprocesa desde**.

Pasos recomendados al contratar una capacidad nueva:
1. Acordar con la agencia la fecha de inicio.
2. Definir `CAPABILITY_<NOMBRE>_REPLAY_SINCE` y encender `CAPABILITY_<NOMBRE>_ENABLED=true`.
3. `php artisan capabilities:status --check` y `php artisan optimize`.
4. Opcional: `php artisan capabilities:catch-up` para procesar de inmediato.


## 8. Retención de la bitácora de eventos

Los eventos entre capacidades se guardan en `integration_events` (con sus entregas en `integration_event_deliveries`). Para que no crezcan sin límite, la tarea diaria `capabilities:prune` borra:

| Qué | Cuándo | Variable (días) |
|---|---|---|
| Eventos que ya recibieron **todas** las capacidades que los usan | Pasados 90 días | `CAPABILITY_EVENTS_DELIVERED_RETENTION_DAYS` |
| Eventos que nadie escucha en este despliegue | Pasados 90 días | `CAPABILITY_EVENTS_DELIVERED_RETENTION_DAYS` |
| Eventos **pendientes** de una capacidad apagada | Pasados 730 días | `CAPABILITY_EVENTS_PENDING_RETENTION_DAYS` |

- **Los datos de negocio no se tocan** (ventas, pagos, facturas, expedientes viven en sus propias tablas). Solo se limpian los mensajes entre capacidades.
- Consecuencia del plazo de pendientes: una capacidad que se enciende después de 730 días apagada **ya no recibe** lo ocurrido antes de ese plazo. Si la agencia la contrata tarde, combínalo con la fecha de inicio (sección 7).
- Horario: `CAPABILITY_EVENTS_PRUNE_CRON` (por defecto 3:30 a. m.); lotes de `CAPABILITY_EVENTS_PRUNE_CHUNK`.
- Ejecución manual: `php artisan capabilities:prune`.
