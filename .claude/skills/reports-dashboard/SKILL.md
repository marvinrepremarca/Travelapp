---
name: reports-dashboard
description: KPIs, reportes y dashboards del negocio de viajes (ventas, margen, conversión, plazos, cartera, proveedores, operación, corporativo, fiscal) y exportaciones. Úsala al crear o modificar un reporte, un KPI, un gráfico, un dashboard o una exportación.
---

# Reportes y dashboard

## Definiciones de KPI (fuente única; implementa cada una en una Query con test)

| KPI | Definición |
|---|---|
| Ventas brutas | Σ precio de venta de ítems confirmados en el período (por fecha de confirmación ⚙ o de viaje) en moneda de la agencia |
| Margen bruto | Σ (venta − neto − costos de pasarela − comisiones pagadas + comisiones recibidas) |
| % margen | Margen / ventas sin impuestos |
| Conversión de cotizaciones | Cotizaciones aceptadas / enviadas (por asesor, canal, destino) |
| Tiempo de respuesta | Mediana de lead creado → primera cotización enviada |
| Ticket promedio | Ventas / expedientes confirmados |
| Cartera por edades | Saldos de clientes 0-30, 31-60, 61-90, > 90 días |
| Cuentas por pagar | Saldo con proveedores por vencimiento |
| Plazos en riesgo | Ítems con fecha límite en < 24 h sin acción |
| Tasa de cancelación | Ítems cancelados / confirmados, y penalidades cobradas vs. pagadas |
| Rendimiento de proveedores | Tasa de error, latencia, % confirmación on-request y tiempo de respuesta |
| Ocupación de salidas | Pasajeros / capacidad por producto propio |
| Cumplimiento de política | % viajes corporativos dentro de política y ahorro |
| NPS / CSAT | Por producto, proveedor, asesor |
| Upsell | Ingreso por servicios añadidos tras la reserva inicial |

Cada KPI documenta: moneda de conversión (tasa del día de la transacción), zona horaria de corte (la de la agencia), y si incluye o no cancelados.

## Implementación
- `app/Modules/Reports/Queries/*` (solo lectura, puede cruzar módulos). Tablas de agregados diarias (`daily_sales_snapshots`) cuando el volumen lo pida, recalculables.
- Filtros estándar: rango de fechas y tipo de fecha (venta/viaje), sucursal, asesor, canal, producto, proveedor, destino, cliente/cuenta corporativa.
- Permisos: márgenes y comisiones solo para roles con permiso; asesores ven sus propias cifras.

## Dashboard
Por rol: asesor (mis cotizaciones, plazos, metas), operaciones (servicios de hoy/mañana, confirmaciones pendientes), finanzas (cartera, cuentas por pagar, conciliación), gerente (ventas, margen, conversión, comparativo con período anterior). Carga diferida por tarjeta, cada cifra con drill-down al listado que la explica.

## Exportaciones
En cola, a CSV/XLSX con protección contra inyección de fórmulas, notificando al terminar con enlace temporal; exportar datos personales queda auditado. Reportes fiscales (FONTUR, retenciones, libro de ventas) con formato validado por finanzas.

## Checklist de cumplimiento
- [ ] KPI definido en la tabla de definiciones (fuente única) e implementado en una Query con test.
- [ ] Moneda de conversión, zona horaria de corte y tratamiento de cancelados explícitos.
- [ ] Filtros estándar y alcance de visibilidad aplicados; márgenes solo con permiso.
- [ ] Cada cifra del dashboard con drill-down; carga diferida.
- [ ] Exportación en cola, protegida contra inyección de fórmulas y auditada si tiene datos personales.
- [ ] `Reports` solo lee; nunca escribe.
