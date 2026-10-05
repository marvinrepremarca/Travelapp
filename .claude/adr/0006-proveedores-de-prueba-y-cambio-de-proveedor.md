# ADR-0006: Proveedores de prueba (Duffel, LiteAPI) y cambio de proveedor por configuración

- **Estado:** Aceptado (2026-10-05). Complementa ADR-0003.
- **Contexto:** Amadeus cerró su portal Self-Service el 17 de julio de 2026; solo queda Amadeus Enterprise (contrato comercial). Se necesitan plataformas de prueba 100 % gratuitas para integrar vuelos y hoteles, y la agencia debe poder cambiar de proveedor sin afectar el sistema.
- **Decisión:**
  - **Vuelos:** Duffel en modo de prueba (aerolínea ficticia "Duffel Airways", ZZ; tokens `duffel_test_`). **Hoteles:** LiteAPI (Nuitée) con clave sandbox. Hotelbeds APItude queda como segundo proveedor de hoteles; Amadeus Enterprise, si la agencia lo contrata.
  - **Puerto por producto** en el módulo consumidor `Search`: `FlightProvider` y `HotelProvider`, con DTOs propios (`FlightOffer`, `HotelOffer`, criterios de búsqueda). El dominio nunca ve JSON de un proveedor.
  - **Un adaptador por proveedor** en `Integrations/Adapters/<Proveedor>`, registrado con una etiqueta del contenedor. Además, un adaptador **Fake** determinista, sin red, para pruebas, demo y continuidad si un proveedor cae.
  - **Selección por configuración:** `TRAVEL_FLIGHT_PROVIDERS` y `TRAVEL_HOTEL_PROVIDERS` (lista separada por comas) eligen los adaptadores activos. Cambiar de proveedor = cambiar `.env`.
  - **Agregador** (`SearchAggregator`): consulta los proveedores activos con tiempo límite, devuelve resultados parciales y la lista de proveedores que fallaron, aplica **circuit breaker** por proveedor (umbral y enfriamiento configurables) y cachea resultados por hash de criterios.
  - Todas las llamadas usan `ProviderHttpClient` (lista blanca de hosts, timeouts, reintentos solo en lecturas, bitácora `supplier_requests`). Las reservas con terceros llevan `idempotency_key` y se re-cotizan antes de confirmar.
- **Consecuencias:**
  - Agregar o reemplazar un proveedor = escribir un adaptador + contract tests con respuestas reales anonimizadas, sin tocar `Search`, `Quotes` ni `Bookings`.
  - Los datos de prueba de Duffel no son realistas (horarios y precios); sirven para validar el flujo, no la oferta comercial.
  - Las credenciales viven solo en `.env` → `config/services.php`.
