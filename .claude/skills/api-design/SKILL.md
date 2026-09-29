---
name: api-design
description: Diseño de la API REST pública/B2B (/api/v1), autenticación con Sanctum, API Resources, errores, paginación, idempotencia, rate limiting, webhooks salientes y documentación OpenAPI. Úsala al crear o modificar endpoints de API, webhooks salientes a clientes/partners o integraciones que otras empresas consumirán.
---

# API REST v1

## Principios
- Recursos en inglés, plural, kebab-case: `/api/v1/bookings/{ulid}/items`. IDs públicos ULID.
- Versionado en la URL; cambios incompatibles solo en una versión nueva. Deprecaciones con cabecera `Deprecation` y fecha.
- Autenticación: tokens Sanctum por integración con **abilities** (`bookings:read`, `bookings:write`, `search`) ligados a un usuario o a una integración; tokens de subagencia B2B limitados a su alcance.
- Toda respuesta pasa por `JsonResource`; nunca devuelvas modelos crudos (evita filtrar columnas).
- Dinero: `{ "amount": "1250.00", "currency": "USD" }` (string decimal, nunca número flotante). Fechas: ISO 8601 con zona; fechas de servicio como `YYYY-MM-DD` + `timezone`.

## Errores (RFC 9457 Problem Details)
```json
{
  "type": "https://docs.travelapp.example/errors/fare-no-longer-available",
  "title": "La tarifa ya no está disponible",
  "status": 422,
  "code": "fare_no_longer_available",
  "detail": "El proveedor retiró la tarifa seleccionada.",
  "errors": { "field": ["mensaje"] },
  "correlation_id": "01J…"
}
```
Códigos: 400/422 validación y reglas, 401, 403, 404 (incluye recursos fuera del alcance del token), 409 conflicto de estado, 429 con `Retry-After`, 5xx sin detalles internos.

## Operaciones seguras
- `POST` que crea reservas, pagos o cancelaciones exige cabecera `Idempotency-Key`; la misma clave devuelve la misma respuesta durante 24 h ⚙.
- Concurrencia optimista con `ETag`/`If-Match` en actualizaciones de expedientes.
- Búsquedas costosas: asincrónicas (`202 Accepted` + `search_id` para consultar resultados) si superan el tiempo ⚙.
- Paginación por cursor (`?cursor=`), `per_page` máximo 100, filtros y orden por lista blanca, `include=` solo relaciones permitidas.
- Rate limiting por token.

## Webhooks salientes (a clientes/partners)
Eventos `booking.confirmed`, `booking.cancelled`, `payment.received`, `booking.item.changed`, etc.; firma HMAC-SHA256 con timestamp, reintentos con backoff durante 24-72 h ⚙, registro de entregas y reenvío manual.

## Documentación
OpenAPI 3.1 generada/actualizada con cada cambio (p. ej. con un generador basado en código, previa aprobación de la dependencia) y publicada para partners; ejemplos reales por endpoint.

## Tests
Por endpoint: auth (sin token 401, ability faltante 403), fuera de alcance 404, validación 422 con estructura Problem Details, idempotencia, paginación y contrato de la respuesta (estructura JSON exacta).

## Checklist de cumplimiento
- [ ] Ruta versionada, plural, ULID; respuesta vía `JsonResource`.
- [ ] Token Sanctum con abilities; 401/403/404 fuera de alcance probados.
- [ ] Errores en formato Problem Details con `code` estable.
- [ ] Dinero como string decimal + moneda; fechas ISO 8601.
- [ ] `Idempotency-Key` en POST con efectos; paginación por cursor; filtros por lista blanca.
- [ ] Rate limiting configurado desde `config/`.
- [ ] OpenAPI actualizado.
