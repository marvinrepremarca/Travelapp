# Changelog — Search

## Fase 2.6 (parte A: puertos, agregador y proveedor Fake)

### Agregado
- **Puertos** `FlightProvider` y `HotelProvider` con DTOs propios (ofertas, tramos, régimen, cancelación); el dominio no conoce ningún proveedor (ADR-0006).
- **Agregador multi-proveedor**: usa solo los proveedores activos en `TRAVEL_FLIGHT_PROVIDERS` / `TRAVEL_HOTEL_PROVIDERS`, devuelve resultados parciales si alguno falla, pausa proveedores con fallos seguidos (circuit breaker) y cachea búsquedas completas.
- **Pantallas** de búsqueda de vuelos y hoteles con precio de venta calculado por Pricing y aviso de resultados parciales o sin disponibilidad.
