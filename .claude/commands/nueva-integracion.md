---
description: Diseña e implementa la integración con un proveedor externo (vuelos, hoteles, autos, actividades, traslados, seguros, pagos, facturación, mensajería) mediante puerto y adaptador.
argument-hint: <proveedor> <tipo de producto o servicio> [ruta o URL de la documentación]
---

Integración: **$ARGUMENTS**

1. **Precondiciones:** documentación de la API y credenciales de sandbox. Si faltan, detente y pídelas. Nunca uses credenciales de producción en desarrollo.
2. **Diseño:** usa el agente `integration-architect` y presenta su documento (capacidades, flujos, mapeos, errores, idempotencia, datos sensibles, fixtures). Espera aprobación del usuario. Crea el ADR con `/adr` si hay decisiones relevantes.
3. **Implementación** (skill `supplier-integrations`, regla `integrations.md`):
   - Adaptador en `app/Modules/Integrations/Adapters/<Proveedor>/` con `README.md` (versión, entornos, límites, particularidades).
   - Mappers, excepciones mapeadas, registro en `ProviderRegistry`, configuración en `config/suppliers.php` y credenciales por entorno en `supplier_connections`.
   - Sincronización de contenido estático si aplica (job programado).
   - Webhooks entrantes si aplica.
4. **Tests:** fixtures anonimizadas en `tests/Fixtures/Suppliers/<Proveedor>/`, contract test del puerto y todos los casos de error. Ninguna llamada real.
5. **Prueba manual en sandbox** (opcional, solo si el usuario lo pide): comando artisan de diagnóstico que no persiste datos de negocio.
6. `security-auditor` + `code-reviewer`, luego `/verificar`.

Reporta en el formato de `CLAUDE.md` §1.
