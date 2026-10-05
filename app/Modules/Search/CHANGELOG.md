# Changelog — Search

## Fase 2.6 (parte A: puertos, agregador y proveedor Fake)

### Agregado
- **Puertos** `FlightProvider` y `HotelProvider` con DTOs propios (ofertas, tramos, régimen, cancelación); el dominio no conoce ningún proveedor (ADR-0006).
- **Agregador multi-proveedor**: usa solo los proveedores activos en `TRAVEL_FLIGHT_PROVIDERS` / `TRAVEL_HOTEL_PROVIDERS`, devuelve resultados parciales si alguno falla, pausa proveedores con fallos seguidos (circuit breaker) y cachea búsquedas completas.
- **Pantallas** de búsqueda de vuelos y hoteles con precio de venta calculado por Pricing y aviso de resultados parciales o sin disponibilidad.

## Fase 2.6 (parte B: de la búsqueda a la reserva)

### Agregado
- **Agregar a cotización** desde los resultados de vuelos y hoteles (contrato `SupplierOfferIntake` de Quotes): el servicio guarda la referencia del proveedor y su venta la calcula Pricing.
- Puertos reservables (`BookableProvider`): re-cotizar, reservar con `idempotency_key` y cancelar; pasarela `SupplierGateway` para que Bookings opere sin conocer el adaptador.
- Al confirmar un servicio de proveedor en el expediente se **re-cotiza**: si el precio cambió o la oferta venció, se informa y no se confirma; si no, se reserva (nunca doble reserva) y el código del proveedor queda como confirmación. Cancelar un servicio confirmado cancela primero con el proveedor. Las llamadas externas van fuera de las transacciones de base de datos.
- Escenarios Fake de cambio de precio: destino `PRC` (vuelos) y ciudad `cambio` (hoteles).
