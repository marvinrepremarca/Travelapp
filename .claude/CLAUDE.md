# TravelApp — Sistema de gestión de la agencia de viajes

Sistema de información de **una** agencia de viajes (con sus sucursales, asesores y canales de venta) que gestiona de forma **modular** todos sus procesos:
- **Gestión viajera:** CRM, cotización e itinerarios, reservas multi-proveedor (aéreo, hotel, autos, tours, pasadías, actividades, traslados, seguros), producto propio, operación en destino, grupos, viajes corporativos, portales y posventa.
- **Administración:** cobros, finanzas y caja, facturación (lista para facturación electrónica futura), liquidación a proveedores, gastos, colaboradores y comisiones, cumplimiento legal y reportes. Se integra con plataformas externas de reservas, empezando por **Amadeus** (vuelos, hoteles, autos, traslados, tours y actividades).

> Idioma: la conversación, la documentación y los textos de UI están en **español**. El código (clases, métodos, tablas, columnas, rutas) está en **inglés**. El glosario negocio→código vive en la skill `travel-domain`.

## 1. Cómo trabajar (obligatorio)

1. **Entiende antes de escribir.** Lee este archivo, las reglas de `.claude/rules/` que apliquen y carga las skills relevantes (tabla §5). Si la tarea toca reglas de negocio, carga siempre `travel-domain`.
2. **Planifica** cambios de más de ~3 archivos: lista archivos, decisiones y riesgos antes de codificar. Si una decisión de negocio es ambigua, **pregunta** en un solo mensaje; no inventes reglas de negocio.
3. **Construye en pasos verticales pequeños** (migración → modelo → action → endpoint/pantalla → tests) y ejecuta los tests de lo tocado en cada paso.
4. **Verifica** con `/verificar` (Definition of Done §6) antes de declarar algo terminado. Nunca digas "listo" con tests o análisis estático fallando.
5. **Reporta** al final, en este formato y nada más:
   ```
   Hecho: <1-3 líneas de qué cambió y por qué>
   Archivos: <lista corta>
   Verificación: Pint ✔ | PHPStan ✔ | Tests ✔ (N) | Cobertura XX %
   Pendiente/Riesgos: <solo si hay>
   ```

## 2. Stack (no cambiar sin ADR)

| Capa | Tecnología |
|---|---|
| Lenguaje / framework | PHP 8.2+, Laravel 12 |
| BD | MySQL 8 (producción) · MariaDB 10.4+ de XAMPP en local (la CI prueba contra MySQL 8). Solo SQL compatible con ambos |
| Cache, colas, locks | Redis + Laravel Horizon (local: `database`) |
| Backoffice y portales | Blade + Livewire 3 + Alpine.js + Tailwind CSS v4 (Vite) |
| API | REST JSON `/api/v1`, Laravel Sanctum, especificación OpenAPI 3.1 |
| Auth / permisos | Laravel Fortify (2FA) · spatie/laravel-permission |
| Dinero | brick/money (nunca `float`) |
| Auditoría | spatie/laravel-activitylog |
| PDF | spatie/laravel-pdf + motor DomPDF (vouchers, itinerarios, cotizaciones; ADR-0005) |
| Calidad | Pest 3 (+ arch tests), Larastan nivel 8, Pint (PER), Rector |
| Observabilidad | Logs JSON estructurados, Laravel Pulse, Sentry |

Entorno local Windows + XAMPP: PHP en `C:\xampp\php\php.exe`. Usa los scripts de `composer.json` (`composer check`) en lugar de comandos sueltos.

## 3. Arquitectura en una página

- **Monolito modular** en `app/Modules/<Modulo>` con límites verificados por arch tests (skill `modular-architecture`, ADR-0001). Los módulos se comunican por `Contracts/` (síncrono) o `Events/` (asíncrono). Nunca se tocan tablas ni Actions de otro módulo.
- **Una sola agencia con sucursales** (ADR-0002): no hay multi-tenancy. La visibilidad de datos se controla por rol y **alcance** (`own` / `branch` / `all`) con scopes de consulta y Policies.
- **Integraciones con proveedores** (Amadeus como plataforma principal; NDC, bancos de camas, rent-a-car, actividades, seguros) detrás de un **puerto por tipo de producto** y un **adaptador por proveedor** (patrón Anti-Corruption Layer; skill `supplier-integrations`, ADR-0003). El dominio no conoce JSON/XML de ningún proveedor.
- **Capas por módulo**: Controller/Livewire (delgado) → FormRequest → DTO → Action (un caso de uso) → Model/Service/Query. Detalle en `laravel-backend`.
- **Dinero** con `Money` + código de moneda ISO 4217; tasas de cambio con fecha y fuente; redondeo solo en la presentación y en la factura (skill `pricing-engine`).
- **Fechas**: UTC en la BD; se muestran en la zona de la agencia. Fechas de servicio (check-in, salida del tour) son **locales al destino** y se guardan como `date` + `timezone` del destino.
- **Idempotencia** en todo lo que cobra o reserva con un tercero (`idempotency_key`), y **sagas con compensación** para reservas multi-ítem.

Mapa de módulos: skill `travel-domain` → `references/modules.md`.

## 4. Reglas no negociables

1. Nunca `float` para dinero ni `DateTime` mutable. `Money` y `CarbonImmutable`.
2. Nunca guardar PAN, CVV ni datos completos de tarjeta: tokenización de la pasarela (PCI DSS SAQ-A).
3. Toda acción autorizada por Policy y todo listado filtrado por el alcance del usuario (propio / sucursal / todo). IDs públicos = ULID.
4. Nunca llamar a un proveedor externo dentro de una transacción de BD ni en el request si puede tardar > 2 s sin timeout: timeouts explícitos, reintentos con backoff, circuit breaker.
5. Toda mutación relevante (precio, estado de reserva, pago, reembolso, datos de pasajero) queda auditada.
6. Datos personales (pasaportes, fechas de nacimiento, salud, menores) cifrados en reposo y fuera de logs (Ley 1581 de 2012 / GDPR).
7. Ninguna dependencia nueva sin aprobación explícita. Ningún secreto en código ni en commits.
8. No debilitar tests, baseline de PHPStan ni umbral de cobertura para "pasar".
9. Textos de UI con `__()`; nada quemado. Accesibilidad WCAG 2.2 AA.
10. **Cero valores quemados**: prohibidos strings mágicos, números mágicos y variables hardcodeadas en backend y frontend (regla `no-hardcoding.md`).
11. **Diseño centralizado**: colores, tipografía, espaciados (margin/padding), radios y sombras solo desde los tokens de diseño (regla `frontend.md`, skill `frontend-ui`).
12. Cada skill termina con un **checklist de cumplimiento**: recórrelo antes de declarar terminada una tarea que la use.

13. **Rendimiento es requisito**, no mejora: presupuestos de la regla `performance.md` en cada pantalla, endpoint y consulta.

Configuración de Claude: este `CLAUDE.md` (raíz) y `.claude/` con `rules/`, `skills/`, `agents/`, `commands/` y `adr/`.

## 5. Skills (cárgalas cuando apliquen)

| Si vas a… | Skill |
|---|---|
| Cualquier regla de negocio de viajes, estados, glosario, módulos | `travel-domain` |
| Integrar/consumir un proveedor externo (vuelos, hoteles, autos, tours, pagos) | `supplier-integrations` |
| Precios, markups, comisiones, impuestos, monedas, políticas de cancelación | `pricing-engine` |
| Crear módulo, ubicar una clase, comunicar módulos | `modular-architecture` |
| Controllers, Actions, DTOs, enums, policies, excepciones | `laravel-backend` |
| Migraciones, índices, seeders, factories | `database-design` |
| Pantallas Blade/Livewire, componentes, tokens | `frontend-ui` |
| Diseñar flujos, formularios, accesibilidad, buscador, checkout | `ux-ui-accessibility` |
| Endpoints `/api/v1`, webhooks, OpenAPI | `api-design` |
| Seguridad, OWASP, PCI, datos personales | `security-owasp` |
| Jobs, eventos, notificaciones, scheduler, correos/WhatsApp | `queues-notifications` |
| Caché, rendimiento, escalabilidad, observabilidad | `performance-scalability` |
| Tests | `testing-pest` |
| KPIs, reportes, dashboards, exportaciones | `reports-dashboard` |

## 6. Definition of Done

- [ ] Cumple el criterio de aceptación y las reglas de `travel-domain`; los casos borde (cancelación, cambio de moneda, zona horaria, disponibilidad agotada, proveedor caído) están cubiertos.
- [ ] Tests: feature (feliz + 403 + validación + fuera de alcance → 404), unit de reglas de negocio, contract tests con fakes para integraciones. Cobertura ≥ 90 % en `app/Modules`.
- [ ] `composer check` en verde (Pint, Larastan 8, Pest + arch).
- [ ] Rendimiento dentro de los presupuestos de `rules/performance.md` (TTFB < 200 ms, ≤ 15 consultas y cero repetidas por pantalla, verificado en `PerformanceBudgetTest`); sin N+1, listados paginados, índices para filtros nuevos.
- [ ] Autorización, alcance de visibilidad, validación, auditoría y datos sensibles revisados.
- [ ] UI: textos en `lang/es`, tokens, estados vacío/carga/error, accesible por teclado, responsive.
- [ ] Sin strings/números mágicos ni valores quemados (backend y frontend).
- [ ] Checklists de cumplimiento de las skills usadas: todos en ✔.
- [ ] Migraciones reversibles; configuración nueva en `config/travel.php` y `.env.example`.
- [ ] ADR si cambia arquitectura, stack o un límite entre módulos. Changelog del módulo si cambia comportamiento visible.

## 7. Comandos

`/inicializar-proyecto` · `/nuevo-modulo` · `/nueva-funcionalidad` · `/nueva-integracion` · `/revisar` · `/verificar` · `/adr`

Agentes: `code-reviewer`, `security-auditor`, `test-engineer`, `travel-domain-analyst`, `integration-architect`, `ux-reviewer`.
