# Changelog — Catalog

## Fase 2.2 (parte 1: producto propio, temporadas, tarifas y cupos)

### Agregado
- **Producto propio** (tour, pasadía, traslado, actividad): código, destino, zona horaria del destino, duración, operador opcional y moneda del costo. Se activa o desactiva; nunca se borra.
- **Temporadas** con fechas inclusivas que no se cruzan, y **costo neto por pasajero** según su tipo (adulto, niño, infante), calculado con la edad a la fecha del servicio. El precio de venta sale de las reglas de Pricing.
- **Salidas** con fecha y hora locales del destino y cupo. Se cierran y reabren sin perder lo apartado.
- **Contrato `CatalogInventory`**: apartar y liberar cupos con bloqueo pesimista e idempotencia; **nunca hay sobreventa**.
- **Contrato `CatalogRates`**: costo neto por temporada y edad, con error explícito si falta temporada o tarifa.
- Permiso `catalog.manage` para gestor de producto y gerencia; todos los usuarios internos consultan el catálogo.
