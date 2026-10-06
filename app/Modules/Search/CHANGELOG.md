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

## Autocompletar de lugares

### Agregado
- **Origen, destino, ciudad y país con autocompletar** por nombre natural ("cartagena", "bogota" sin tilde, "el dorado"); el sistema guarda y envía a los proveedores el código **IATA** (aeropuertos) o **ISO 3166-1** (países). Elegir una ciudad de hotel completa su país; una ciudad sin aeropuerto se acepta tal como se escribe.
- Lo escrito sin elegir se traduce si no es ambiguo; si no, se pide elegir de la lista. Navegable con teclado (flechas, Enter, Escape), patrón combobox de WAI-ARIA (`x-ui.combobox`).
- Datos de referencia: tabla `airports` con `AirportsSeeder` idempotente (≈150 aeropuertos, prioridad a destinos de la agencia); países desde ICU en el idioma de la aplicación. Parámetros ⚙ `autocomplete_min_chars` y `autocomplete_limit`.
