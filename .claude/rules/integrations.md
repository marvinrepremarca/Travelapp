---
paths:
  - "app/Modules/Integrations/**/*.php"
  - "app/Modules/**/Integrations/**/*.php"
  - "config/suppliers.php"
---

# Reglas: integraciones con proveedores externos

Detalle en la skill `supplier-integrations`.

1. El dominio depende del **puerto** (`FlightProvider`, `HotelProvider`, `CarRentalProvider`, `ActivityProvider`, `TransferProvider`, `InsuranceProvider`, `PaymentGateway`, `EInvoicingProvider`), nunca del adaptador.
2. El adaptador traduce a/desde DTOs del dominio. Ningún tipo, código ni estructura del proveedor sale del adaptador; los códigos se mapean a enums propios y lo desconocido va a `Unknown` + log de advertencia.
3. Cliente HTTP con `timeout`/`connectTimeout` explícitos, reintentos con backoff exponencial **solo** en operaciones idempotentes (búsqueda, consulta), circuit breaker y rate limiting por proveedor.
4. Operaciones que crean compromisos (reservar, emitir, cobrar, cancelar) llevan `idempotency_key`; tras un timeout **se consulta el estado** antes de reintentar. Nunca doble reserva ni doble cobro.
5. Toda llamada queda en `supplier_requests` (proveedor, operación, duración, resultado, `correlation_id`) con payload **redactado** (sin tarjetas, documentos ni credenciales) y retención configurable.
6. Antes de confirmar se revalidan precio y disponibilidad (prebook / price check). Si cambian, se informa al usuario; nunca se absorbe en silencio.
7. Webhooks entrantes: firma verificada, idempotentes por id de evento, procesados en cola.
8. Credenciales por proveedor y por entorno (sandbox / producción), cifradas; nunca en el repositorio.
