# Changelog — Integrations

## Fase 2.1

### Agregado
- `ProviderHttpClient`: hosts salientes en lista blanca, timeouts, reintentos y bitácora de cada llamada en `supplier_requests`.
- Adaptador **datos.gov.co (TRM)** para el puerto `OfficialExchangeRateSource` de Pricing, con contract tests sobre respuestas reales.

## Fase 2.6 (parte A)

### Agregado
- Adaptadores **Fake** de vuelos y hoteles (sin red, deterministas) para pruebas, demo y continuidad; escenarios de falla (`ERR` / `error`) y sin disponibilidad (`NON` / `agotado`).
- Registro de adaptadores por etiqueta del contenedor; agregar un proveedor = agregar su adaptador y su clave en `.env`.
