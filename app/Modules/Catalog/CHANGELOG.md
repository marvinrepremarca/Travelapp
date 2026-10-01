# Changelog — Catalog

## Fase 2.2 (parte 1: producto propio, temporadas, tarifas y cupos)

### Agregado
- **Producto propio** (tour, pasadía, traslado, actividad): código, destino, zona horaria del destino, duración, operador opcional y moneda del costo. Se activa o desactiva; nunca se borra.
- **Temporadas** con fechas inclusivas que no se cruzan, y **costo neto por pasajero** según su tipo (adulto, niño, infante), calculado con la edad a la fecha del servicio. El precio de venta sale de las reglas de Pricing.
- **Salidas** con fecha y hora locales del destino y cupo. Se cierran y reabren sin perder lo apartado.
- **Contrato `CatalogInventory`**: apartar y liberar cupos con bloqueo pesimista e idempotencia; **nunca hay sobreventa**.
- **Contrato `CatalogRates`**: costo neto por temporada y edad, con error explícito si falta temporada o tarifa.
- Permiso `catalog.manage` para gestor de producto y gerencia; todos los usuarios internos consultan el catálogo.

## Fase 2.2 (parte 2: paquetes prearmados)

### Agregado
- **Paquetes**: producto de tipo paquete que combina productos propios por día del itinerario (día 1, 2…). No incluye otros paquetes y todos sus componentes cuestan en su moneda.
- El **costo neto del paquete** suma cada componente en su fecha real y temporada; las edades se toman al inicio del paquete.
- **Precio "desde"** (neto por adulto): el menor entre las temporadas vigentes o futuras; en un paquete, la suma de sus componentes. Se muestra en la ficha.
- No se puede cambiar el tipo ni la moneda de un producto que es paquete o componente de uno.
- Hoteles y vuelos no son producto propio: se combinan con el paquete al cotizar (Fase 2.3).
