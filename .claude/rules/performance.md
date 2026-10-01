# Reglas: rendimiento — requerimientos no funcionales (siempre activas)

Detalle en la skill `performance-scalability`. El sistema debe sentirse **instantáneo**: el usuario nunca espera al servidor para trabajar.

<presupuestos>
| Métrica (producción, p95 salvo indicación) | Objetivo | Límite duro |
|---|---|---|
| Pantalla de backoffice, tiempo de servidor (TTFB) | < 100 ms | 200 ms |
| Interacción Livewire (filtrar, guardar, cambiar pestaña) | < 100 ms | 200 ms |
| API `/api/v1` de lectura | < 80 ms | 150 ms |
| API de escritura / checkout sin proveedor | < 200 ms | 400 ms |
| Carga completa de la página en el navegador (LCP, red de oficina) | < 1 s | 1,5 s |
| Consultas SQL por pantalla o request | ≤ 10 | ≤ 15 (`travel.performance.max_queries_per_screen`) |
| Consultas repetidas (misma SQL + bindings) en un request | 0 | 0 |
| Consulta individual | < 10 ms | 50 ms (se registra como lenta) |
| JS + CSS iniciales (gzip) | < 100 KB | 150 KB |
| Búsqueda multi-proveedor | primeros resultados < 2 s | completa < 8 s, siempre en streaming/lazy |
</presupuestos>

<obligatorio>
1. **Medir antes y después.** Todo PR que toque una pantalla, consulta o endpoint reporta consultas y tiempo (test de presupuesto y, si aplica, `EXPLAIN`).
2. **Presupuesto automatizado:** cada pantalla nueva entra al dataset de `tests/Feature/Shared/PerformanceBudgetTest.php` (máximo de consultas y cero repetidas, con datos de demostración). No se sube el límite para que pase (regla 8 de CLAUDE.md).
3. **Sin N+1:** `Model::shouldBeStrict()` activo fuera de producción; `with()`, `withCount()` y conteos agrupados (`groupBy`) en lugar de una consulta por fila o por columna.
4. **Lecturas repetidas memorizadas por request:** configuración, perfil de la agencia y catálogos con `Cache::memo()` u `once()`; nunca releer lo mismo en un request. Nada de `refresh()`/`fresh()` en `render()`.
5. **Solo lo visible:** pestañas, modales y secciones plegadas consultan solo cuando se muestran; listados siempre paginados o limitados por config; columnas explícitas en listados grandes.
6. **Índices** para cada filtro, orden y join nuevos, verificados con `EXPLAIN` (regla `database.md`).
7. **Nada lento en el request:** proveedores, PDFs, correos, exportaciones y sincronizaciones en cola; lo interactivo con proveedor va con `#[Lazy]`, timeout y resultados parciales.
8. **Caché con intención:** TTL desde config, invalidación por evento o acción, sin datos personales en caché compartida.
9. **Frontend liviano:** sin librerías JS nuevas sin aprobación, imágenes optimizadas y con dimensiones, `wire:model.live` solo con debounce.
</obligatorio>

<entorno>
- **Producción:** OPcache activo (`validate_timestamps=0`, precarga opcional), sin Xdebug, `composer install --no-dev --optimize-autoloader --classmap-authoritative`, `php artisan optimize` en cada despliegue, PHP-FPM (o FrankenPHP/Octane tras ADR), Redis para caché/sesión/colas, `APP_DEBUG=false`, `LOG_LEVEL=warning`.
- **Local (XAMPP):** OPcache activo y **Xdebug con `xdebug.mode=off`** en `php.ini`; la cobertura lo enciende sola (`composer test:coverage` usa `XDEBUG_MODE=coverage`). Con Xdebug en modo `coverage`/`debug` cada pantalla tarda 10× más: no es representativo del sistema.
</entorno>
