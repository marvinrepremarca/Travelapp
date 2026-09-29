---
name: supplier-integrations
description: Diseño e implementación de integraciones con proveedores externos de viajes y servicios (GDS/NDC aéreo, bancos de camas y channel managers, rent-a-car, actividades y tours, traslados, seguros, estado de vuelos, pasarelas de pago, facturación electrónica, WhatsApp). Úsala al crear o modificar un adaptador, un puerto, un webhook, mapeos de códigos, caché de búsqueda, reintentos, circuit breakers o fixtures de proveedores.
---

# Integraciones con proveedores

## Arquitectura: puertos y adaptadores (Anti-Corruption Layer)

```
app/Modules/Integrations/
├── Contracts/                 # [PÚBLICO] Puertos por tipo de producto
│   ├── HotelProvider.php      # search(), checkRate(), book(), retrieve(), cancel()
│   ├── FlightProvider.php     # search(), price(), book(), issue(), retrieve(), cancel(), void()
│   ├── CarRentalProvider.php
│   ├── ActivityProvider.php
│   ├── TransferProvider.php
│   └── InsuranceProvider.php
├── Data/                      # [PÚBLICO] DTOs de dominio: HotelSearchCriteria, HotelOffer, BookingConfirmation…
├── Enums/                     # [PÚBLICO] SupplierCode, ProviderCapability, ProviderErrorType
├── Exceptions/                # ProviderUnavailable, OfferExpired, PriceChanged, SoldOut, InvalidProviderResponse
├── Support/                   # ProviderHttpClient, CircuitBreaker, RequestLogger, Redactor
├── Registry/ProviderRegistry.php   # Resuelve el adaptador habilitado por tipo de producto
└── Adapters/
    └── <Proveedor>/
        ├── <Proveedor>HotelAdapter.php   # implements HotelProvider
        ├── Mappers/                      # Proveedor → DTO dominio (y viceversa)
        ├── Requests/                     # Construcción de payloads
        └── README.md                     # Versión de API, sandbox, particularidades, límites
```

- Los **pagos** (`PaymentGateway`) y la **facturación electrónica** (`EInvoicingProvider`) siguen el mismo patrón en sus módulos (`Payments`, `Invoicing`). La facturación electrónica **no se integra todavía**: existe el puerto y el adaptador `NullEInvoicingProvider` (ADR-0004).
- Un puerto expone **capacidades** (`supports(ProviderCapability::Cancel)`): no todos los proveedores permiten todo; la UI se adapta.

## Amadeus: plataforma principal

Amadeus es la primera y principal integración de la agencia. Cubre vuelos, hoteles, autos, traslados, y tours y actividades.

- **Primero confirma el producto contratado.** Antes de diseñar nada, confirma con el usuario qué producto de Amadeus tiene la agencia:
  - Amadeus Enterprise / Web Services (SOAP/XML, con PNR completo y emisión).
  - Las REST APIs (JSON con OAuth2).
  - Otra solución de Amadeus.

  También confirma su disponibilidad vigente y las condiciones comerciales: la oferta de APIs de Amadeus cambia con el tiempo. No asumas endpoints ni versiones de memoria; trabaja con la documentación y el sandbox que entregue el usuario.
- **Un adaptador por capacidad**, dentro de `Adapters/Amadeus/`: `AmadeusFlightAdapter`, `AmadeusHotelAdapter`, `AmadeusCarAdapter`, `AmadeusTransferAdapter` y `AmadeusActivityAdapter`. Todos comparten `AmadeusClient`, que se ocupa de:
  - la autenticación y la renovación del token o la sesión;
  - el entorno (test o producción);
  - la oficina (office ID);
  - el rate limiting;
  - el registro redactado de las llamadas.
- **Flujos de referencia** (valídalos contra la documentación contratada):
  - **Vuelos:** búsqueda de ofertas → confirmación de precio → creación de la orden/PNR con los datos de pasajeros → emisión (según el contrato: en Amadeus o a través de un consolidador) → consulta, cancelación y void. El TTL se guarda en `ticketing_deadline_at`. Los cambios de itinerario llegan por colas o notificaciones y generan alerta.
  - **Hoteles:** búsqueda por ciudad o geocódigo → ofertas por hotel → verificación de la oferta → reserva con los datos de huésped y garantía de pago tokenizada → cancelación. El contenido estático se sincroniza aparte.
  - **Autos y traslados:** búsqueda → reserva → cancelación, con los datos del vuelo en los traslados.
  - **Tours y actividades:** búsqueda por ubicación → detalle. Si la reserva se completa en el sitio del operador (enlace de reserva), el ítem queda `on_request` hasta que se registre el localizador. Nunca se asume confirmado.
- **Identificador obligatorio:** el localizador del PNR/orden de Amadeus se guarda en `supplier_reference`, y los números de tiquete en los detalles del ítem aéreo.
- **Riesgos:** los precios cambian entre la búsqueda y la reserva, y los límites de peticiones pueden bloquear. La emisión tiene costo: se aplican los permisos y las confirmaciones humanas de la skill `travel-domain`.

## Catálogo de proveedores típicos (para diseñar puertos genéricos)

| Tipo | Ejemplos de mercado | Notas de integración |
|---|---|---|
| Aéreo | Amadeus, Sabre, Travelport (GDS); NDC de aerolíneas o agregadores NDC; consolidadores | Sesiones/tokens, PNR, TTL, colas de cambios, emisión, EMD, BSP |
| Hotel | Hotelbeds, WebBeds, Expedia Rapid, TBO, RateHawk; channel managers | `search → checkRate → book`, contenido estático separado (sincronización nocturna), mapeo GIATA |
| Autos | CarTrawler, Rentalcars/Booking, APIs directas de rentadoras | Códigos ACRISS, oficinas, coberturas |
| Actividades | Viator, GetYourGuide, Civitatis, Bókun, OCTO (estándar abierto) | Disponibilidad por fecha/hora, opciones, preguntas de reserva |
| Traslados | Hotelbeds Transfers, proveedores locales, Mozio | Datos de vuelo obligatorios |
| Seguros | Aseguradoras/asistencias con API | Emisión y certificado |
| Estado de vuelos | FlightAware, Cirium, OAG, AeroDataBox | Webhooks/polling para alertas |
| Pagos | Wompi, PayU, Mercado Pago, Stripe, Adyen | Hosted checkout/tokenización, webhooks, 3DS |
| Facturación (futuro) | Proveedores tecnológicos habilitados por la DIAN | Envío, estado, CUFE, PDF/XML. No implementar hasta que se apruebe |
| Mensajería | WhatsApp Business Cloud API, SMS, e-mail transaccional | Plantillas aprobadas, opt-in |

Estos nombres son referencia de mercado: **no implementes ninguno sin que el usuario lo elija** y aporte documentación y credenciales de sandbox.

## Reglas de implementación

1. **Cliente HTTP** (`ProviderHttpClient`): `timeout` de búsqueda ⚙ (≈ 8-15 s), `connectTimeout` 3 s, reintentos solo en operaciones idempotentes, `correlation_id` en cabeceras, registro en `supplier_requests` con payload redactado.
2. **Circuit breaker** por proveedor (en Redis): abre tras N errores/timeouts ⚙ en una ventana; mientras está abierto el proveedor se omite de la búsqueda y se notifica en el panel de salud.
3. **Búsqueda agregada:** consulta proveedores en paralelo (`Http::pool` o jobs con batch), devuelve lo que llegue dentro del tiempo límite, deduplica, aplica `Pricing` y cachea resultados ⚙ (5-15 min) por `hash(criterios + agencia)`. La oferta cacheada **nunca** se reserva sin `checkRate`.
4. **Reserva:** `idempotency_key`; si hay timeout, `retrieve()` por la clave o referencia del cliente antes de reintentar. Si el estado es desconocido, el ítem queda `on_request` + tarea urgente (nunca se asume ni fallo ni éxito).
5. **Contenido estático** (hoteles, fotos, descripciones, aeropuertos, oficinas de autos) se sincroniza con jobs programados a tablas propias; la búsqueda no descarga contenido.
6. **Mapeo:** tablas `supplier_code_mappings` (proveedor, tipo, código externo → código interno). Nunca `match` con cientos de códigos en el adaptador.
7. **Errores:** el adaptador traduce cada error a una excepción del dominio con `ProviderErrorType` (`Timeout`, `SoldOut`, `PriceChanged`, `InvalidRequest`, `AuthFailed`, `RateLimited`, `Unknown`). El mensaje original va al log, no al usuario.
8. **Webhooks** en `/webhooks/{provider}`: verificación de firma, tabla `inbound_webhooks` con id único (idempotencia), respuesta 2xx inmediata y procesamiento en cola.
9. **Credenciales:** en `supplier_connections` (cast `encrypted`) por proveedor y entorno `sandbox|production`, con flag de activo y rotación; nunca en el repositorio.
10. **Versionado:** cada adaptador documenta la versión de API en su README; un cambio de versión se hace como adaptador nuevo o con feature flag.

## Tests obligatorios de un adaptador

Con `Http::fake()` y fixtures reales anonimizados (`tests/Fixtures/Suppliers/<Proveedor>/<operacion>_<caso>.json|xml`):
- Mapeo completo de una respuesta OK a DTOs (assert de cada campo relevante, incluidos impuestos y política de cancelación).
- Sin resultados / agotado, precio cambiado en `checkRate`, timeout, 5xx, 401, respuesta malformada.
- Idempotencia: la segunda llamada con la misma clave no crea otra reserva.
- Redacción: el log no contiene documentos, tarjetas ni credenciales.
- Contract test compartido: un dataset de "comportamiento de puerto" que **todo** adaptador de ese tipo debe pasar.

## Checklist para una integración nueva
Usa el comando `/nueva-integracion`. El agente `integration-architect` revisa el diseño antes de codificar.

## Checklist de cumplimiento
- [ ] El dominio usa el puerto; ningún tipo o código del proveedor sale del adaptador.
- [ ] Timeouts, reintentos solo en operaciones idempotentes, circuit breaker y rate limit configurados (desde `config/suppliers.php`).
- [ ] `idempotency_key` en reservar/emitir/cobrar/cancelar; consulta de estado antes de reintentar.
- [ ] Revalidación de precio y disponibilidad antes de confirmar.
- [ ] Llamadas registradas en `supplier_requests` con payload redactado.
- [ ] Credenciales cifradas por entorno, fuera del repositorio.
- [ ] Webhooks con firma, idempotentes y procesados en cola.
- [ ] Contract test + casos OK, sin disponibilidad, precio cambiado, timeout, 5xx, malformado; ninguna llamada real.
- [ ] `README.md` del adaptador con versión de API y particularidades.
