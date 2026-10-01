---
name: performance-scalability
description: Rendimiento, escalabilidad, caché, concurrencia y observabilidad. Úsala al optimizar consultas o pantallas lentas, diseñar la búsqueda agregada, cachear, manejar picos de tráfico (temporadas, promociones), dimensionar workers, o agregar métricas, trazas y health checks.
---

# Rendimiento, escalabilidad y observabilidad

## Presupuestos
Los requerimientos no funcionales de rendimiento (TTFB, consultas por pantalla, tamaño de assets, búsqueda) están en la regla `.claude/rules/performance.md` y son obligatorios. Disponibilidad: 99,9 % mensual.

## Reglas
1. Medir antes de optimizar (Pulse, `EXPLAIN`, Debugbar en local). Nada de optimizaciones especulativas.
2. **Sin N+1**: `preventLazyLoading` activo; `with()` y `withCount()` en listados.
3. **Caché con intención**: `Cache::flexible()` (stale-while-revalidate) para catálogos y configuración; resultados de búsqueda por hash de criterios; invalidación por eventos. Nunca cachear respuestas con datos personales en caché compartida.
4. **Trabajo pesado en colas**: PDFs, correos, exportaciones, sincronizaciones, llamadas no interactivas.
5. **Búsqueda agregada**: fan-out en paralelo con tiempo límite, resultados parciales, circuit breaker, precarga de contenido estático, respuesta en streaming/lazy a la UI.
6. **Concurrencia**: bloqueo pesimista para inventario/capacidad; `Cache::lock` para operaciones por expediente; idempotencia en todo lo externo.
7. **Stateless**: sesiones y caché en Redis, archivos en almacenamiento de objetos (S3-compatible) → escalado horizontal detrás de balanceador. Octane solo tras ADR y pruebas de fugas de estado entre requests.
8. **Base de datos**: índices guiados por consultas reales, réplicas de lectura para `Reports` cuando la carga lo justifique, archivado de bitácoras voluminosas.
9. **Frontend**: assets versionados en CDN, imágenes optimizadas, Livewire con `#[Lazy]` y payloads pequeños.

## Observabilidad
- Logs JSON con `correlation_id` (propagado a jobs y a proveedores), `branch_id`, `user_id`, duración.
- Métricas de negocio y técnicas: latencia y tasa de error **por proveedor**, conversión búsqueda→reserva, reservas fallidas por causa, colas (tamaño, espera), jobs fallidos.
- `/health` (BD, Redis, colas, scheduler heartbeat, espacio) para el balanceador; panel interno de salud de proveedores.
- Errores a Sentry con contexto sin datos personales.

## Pruebas de carga
Antes de temporadas altas o lanzamientos: escenarios de búsqueda y checkout con proveedores simulados (k6 u otra herramienta aprobada), objetivo documentado en el ADR correspondiente.

## Checklist de cumplimiento
- [ ] Se midió antes de optimizar (evidencia en el PR) y se respetan los presupuestos de `performance.md`.
- [ ] Pantalla nueva agregada a `PerformanceBudgetTest` (consultas ≤ límite, cero repetidas).
- [ ] Sin N+1; listados paginados; índices verificados.
- [ ] Caché con TTL desde configuración, invalidación definida y sin datos personales compartidos.
- [ ] Trabajo pesado en cola; llamadas externas con timeout y paralelizadas cuando aplica.
- [ ] Concurrencia protegida (locks) en inventario, pagos y estados.
- [ ] Logs con `correlation_id`; métricas por proveedor; SLO del cambio respetados.
