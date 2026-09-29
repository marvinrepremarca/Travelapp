---
paths:
  - "database/**/*.php"
  - "app/Modules/**/Database/**/*.php"
---

# Reglas: base de datos (MySQL 8 / MariaDB 10.6+)

<migraciones>
- Clase anónima, una responsabilidad por migración, `down()` funcional. Nunca edites una migración ya ejecutada fuera de tu máquina.
- Tablas en inglés, snake_case, plural (`bookings`, `booking_items`). Pivotes en singular y orden alfabético.
- Las tablas operativas (expedientes, cotizaciones, cobros, caja, facturas) llevan `branch_id` y el usuario responsable (`owner_id`) para filtrar por alcance y reportar por sucursal y asesor.
- FKs con acción explícita. Identificador público `ulid('ulid')->unique()`; PK interna `id` bigint.
- Estados: `string('status', 30)` + enum PHP. Nada de columnas `ENUM`.
- Dinero: `bigInteger('amount_minor')` + `char('currency', 3)`. Tasas de cambio: `decimal(18, 8)`. Porcentajes: `decimal(7, 4)` o puntos básicos enteros. **Nunca** `float`/`double`.
- Instantes (`confirmed_at`, `ticketing_deadline_at`): `dateTime()` en UTC. Fechas de servicio: `date()`/`time()` locales + columna `timezone` del destino cuando aplique.
- Datos personales sensibles (pasaporte, documento, fecha de nacimiento, notas médicas): `text` con cast `encrypted` y columna `*_hash` (HMAC) si hay que buscar por ellos.
- `softDeletes()` en entidades comerciales (clientes, reservas, proveedores, productos). Los movimientos financieros **nunca se borran ni se editan**: se anulan con un movimiento inverso.
- JSON solo para datos no consultables (payload de proveedor, snapshot de tarifa y políticas). Si se filtra por un campo, es una columna.
</migraciones>

<seeders_y_factories>
- Datos de referencia (monedas, países, aeropuertos/ciudades IATA, tipos de producto, impuestos, roles, permisos): idempotentes con `updateOrCreate()` por llave natural.
- Datos demo solo en `local`/`testing`. Cada modelo con factory y states por estado de negocio (`->confirmed()`, `->cancelled()`, `->withFlight()`).
</seeders_y_factories>
