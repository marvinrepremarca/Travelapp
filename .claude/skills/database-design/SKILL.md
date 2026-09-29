---
name: database-design
description: Diseño de base de datos del proyecto (MySQL 8 / MariaDB). Úsala al crear o modificar tablas, migraciones, índices, relaciones, columnas de dinero/fechas/datos sensibles, seeders de referencia (monedas, países, aeropuertos), factories, particionado/retención de logs, o al optimizar consultas.
---

# Base de datos

## Antes de crear una tabla, responde
1. ¿De qué módulo es? (solo ese módulo escribe en ella)
2. ¿Es operativa (lleva `branch_id` y responsable para el alcance de visibilidad) o de referencia?
3. ¿Qué filtros y ordenamientos tendrá el listado principal? → índices.
4. ¿Tiene datos personales? → cifrado + hash + política de retención.
5. ¿Es transaccional-financiera? → inmutable (append-only).

## Plantilla

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table): void {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('product_type', 30);
            $table->string('status', 30);
            $table->string('supplier_reference', 100)->nullable();
            $table->date('service_start_date');
            $table->date('service_end_date');
            $table->string('service_timezone', 64);
            $table->bigInteger('net_amount_minor');
            $table->char('net_currency', 3);
            $table->bigInteger('sale_amount_minor');
            $table->char('sale_currency', 3);
            $table->decimal('exchange_rate', 18, 8);
            $table->json('cancellation_policy');        // snapshot, no consultable
            $table->dateTime('ticketing_deadline_at')->nullable();
            $table->string('idempotency_key', 80)->unique();
            $table->timestamps();
            $table->softDeletes();

            // Tablero de plazos: filtra por estado y vencimiento
            $table->index(['status', 'ticketing_deadline_at']);
            // Operación diaria: servicios por fecha
            $table->index(['branch_id', 'service_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
```

## Convenciones
- Detalles por tipo de producto en tablas hijas 1:1 (`booking_item_flight_details`, `booking_item_hotel_details`) en lugar de decenas de columnas nulas.
- Pasajeros por ítem: pivote `booking_item_traveler` (no todos los pasajeros van en todos los servicios).
- Catálogos de referencia: `countries`, `currencies`, `airports`, `cities`, `airlines`, `hotel_static_content`, `exchange_rates` (oficiales y propias de la agencia, diferenciadas por `source`).
- Movimientos financieros (`ledger_entries`, `payments`, `refunds`, `invoices`) sin `softDeletes` ni `update` de montos.
- Versionado de documentos y cotizaciones con tablas `*_versions`.
- Bitácoras voluminosas (`supplier_requests`, `activity_log`, `inbound_webhooks`) con índice por fecha y job de purga/archivo por retención ⚙.

## Rendimiento
- `EXPLAIN` para toda consulta nueva de listado o reporte; nada de `LIKE '%x%'` sobre tablas grandes (usa FULLTEXT o un motor de búsqueda si hace falta).
- Paginación por cursor en listados grandes y API; `chunkById`/`lazyById` en procesos masivos.
- Contadores y saldos frecuentes pueden materializarse, pero siempre recalculables desde la fuente.
- Consistencia en cupos/capacidad: `SELECT … FOR UPDATE` sobre la fila de inventario dentro de la transacción de reserva.

## Seeders
- Referencia (todos los entornos, idempotentes): monedas ISO 4217, países ISO 3166, aeropuertos/ciudades IATA, tipos de producto, roles y permisos, impuestos base.
- Demo (`local`): una agencia con sucursales, usuarios por rol, proveedores manuales, productos propios con temporadas, expedientes en cada estado.

## Checklist de cumplimiento
- [ ] Migración anónima, reversible, una responsabilidad; no se editó una migración ya ejecutada.
- [ ] `ulid` público, FKs con acción explícita, `branch_id`/`owner_id` en tablas operativas.
- [ ] Dinero en `*_amount_minor` + `*_currency`; tasas `decimal(18,8)`; sin `float`/`ENUM`.
- [ ] Instantes en UTC; fechas de servicio con zona del destino.
- [ ] Datos personales cifrados con hash de búsqueda si aplica.
- [ ] Índices justificados para los filtros reales (verificado con `EXPLAIN`).
- [ ] Movimientos financieros inmutables; bitácoras con retención.
- [ ] Factory con states por estado de negocio; seeders de referencia idempotentes.
