# Changelog — Reports

## Fase 3.8 (tableros por rol)

### Agregado
- **Tablero de gerencia** (permiso de márgenes): ventas, margen, % margen, expedientes y ticket promedio del mes con variación contra el anterior; ventas por día (gráfico SVG accesible); embudo leads → cotizaciones enviadas → aceptadas → expedientes con conversión; desgloses por sucursal, asesor y tipo de producto.
- **Mi tablero** (asesor): mis ventas y conversión del mes, cotizaciones por vencer, leads por atender y próximos viajes.
- **Tablero de finanzas**: cartera por vencimiento (vencida, vence en N días ⚙, posterior), cuentas por pagar por vencimiento, cajas abiertas con el efectivo esperado y facturación del mes por tipo de documento.
- Cada cifra con enlace al listado que la explica y carga diferida. **Exportación CSV** (ventas por asesor/sucursal, cartera) dentro del alcance, protegida contra inyección de fórmulas y auditada.
- Definiciones de KPI en las Queries (`app/Modules/Reports/Queries`): fecha de venta, moneda de la agencia, corte en su zona horaria y cancelados solo con penalidades.
