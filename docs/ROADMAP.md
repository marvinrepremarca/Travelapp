# TravelApp — Plan de trabajo por fases y módulos

Sistema de gestión de **una** agencia de viajes con sucursales (ADR-0002), construido como monolito modular Laravel 12 (ADR-0001). Mapa completo de módulos: `.claude/skills/travel-domain/references/modules.md`.

## 1. Cómo se entrega cada pieza (aplica a todos los entregables)

Cada entregable es un **incremento vertical** (migración → modelo → action → endpoint/pantalla → tests) que se puede verificar de forma independiente.

| Paso | Qué | Evidencia verificable |
|---|---|---|
| 1. Especificación | Historias de usuario con criterios de aceptación (Given/When/Then), reglas, estados y casos borde | `docs/specs/<modulo>/<funcionalidad>.md` |
| 2. Rama | `feature/<fase>-<modulo>-<funcionalidad>` desde `main` | Rama en GitHub |
| 3. Código + tests | Feature (feliz, 403, validación, fuera de alcance → 404), unit de reglas, arch tests, contract tests con fakes para proveedores | `composer test` |
| 4. Verificación | `composer check`: Pint + Larastan 8 + Pest (+ arch) + cobertura ≥ 90 % en `app/Modules` + `composer audit` | CI de GitHub Actions en verde |
| 5. Revisión | Agentes `code-reviewer`, `security-auditor`, `ux-reviewer` (si hay UI) | Hallazgos resueltos en el PR |
| 6. Integración | Pull Request → `main` (squash), changelog del módulo | PR fusionado |
| 7. Hito | Al cerrar cada fase: tag semántico `vX.Y.0` + release notes + demo con datos de seed | Release en GitHub |

**Ningún entregable se considera terminado** si la CI no está en verde o si falta alguno de los criterios de la Definition of Done (`.claude/CLAUDE.md` §6).

Convenciones de Git: Conventional Commits (`feat(bookings): …`), `main` protegida (requiere CI verde), nunca secretos en el repo (`.env` ignorado, `.env.example` versionado).

## 2. Fases

### Fase 0 — Fundaciones técnicas · `v0.1.0`

Objetivo: un esqueleto que ya exige la calidad desde el primer commit.

| # | Entregable | Criterio de aceptación verificable |
|---|---|---|
| 0.1 ✔ | Repositorio, `.gitignore`, `README`, este roadmap | Publicado en GitHub |
| 0.2 ✔ | Laravel 12 + `/inicializar-proyecto` (Pint, Larastan 8, Pest 3, Rector, estructura `app/Modules`, Fortify con 2FA, roles base, tokens y componentes `x-ui`) | `composer check` en verde en local |
| 0.3 | CI GitHub Actions: PHP 8.2, MySQL 8; Pint, PHPStan, Rector, Pest con cobertura, `composer audit`, `npm audit`, build Vite | Pipeline verde en PR |
| 0.4 ✔ | Módulo `Shared`: `Money` (brick/money), `DateRange`, `PassengerMix`, `BusinessRuleException`, enums transversales, trait `HasVisibilityScope` | Unit tests con datasets (redondeo, prorrateo `allocate`, monedas 0/2 decimales) |
| 0.5 ✔ | Arch tests de límites entre módulos, `preventLazyLoading`, logs JSON, health check `/health` (BD y caché) | Test arch falla si un módulo usa Models/Actions de otro |
| 0.6 ✔ | Tokens de diseño (`resources/css/tokens.css`), layout backoffice, componentes `x-ui` base, `lang/es` | Página de muestra accesible (teclado, contraste AA) |

### Fase 1 — Base de la agencia · `v0.2.0`

| # | Módulo | Entregables | Verificación clave |
|---|---|---|---|
| 1.1 ✔ | `Organization` | Datos de la agencia, sucursales, `AppSettings` (config + tabla `settings`), festivos | Feature tests de CRUD con Policy; settings editables con valor por defecto de `config/travel.php` |
| 1.2 ✔ | `Identity` | Login Fortify + 2FA, roles/permisos (enum + spatie), alcance `own/branch/all`, usuarios internos | Tests: 2FA obligatorio por rol, usuario fuera de alcance recibe 404 |
| 1.3 ✔ | `Audit` | Bitácora de cambios y de accesos a datos sensibles | Test: toda mutación de pasajero/precio genera registro |
| 1.4 ✔ | `Workflow` | Tareas, recordatorios, aprobaciones genéricas | Test de ciclo solicitar → aprobar/rechazar con eventos |
| 1.5 ✔ | `Crm` | Clientes (persona/empresa), pasajeros con documentos cifrados, leads, oportunidades, embudo, interacciones | Tests de cifrado en reposo y enmascarado en UI/logs; consentimiento Ley 1581 |
| 1.6 ✔ | `Suppliers` | Proveedores, contratos, condiciones de pago, comisiones pactadas, contactos | Tests de validación y alcance |

### Fase 2 — Vender · `v0.3.0`

| # | Módulo | Entregables | Verificación clave |
|---|---|---|---|
| 2.1 ✔ | `Pricing` | Markups, fees, comisiones, impuestos, tasas de cambio con fecha/fuente, redondeo | Datasets de precios y conversiones; margen siempre derivado |
| 2.2 ✔ | `Catalog` | Producto propio (tours, pasadías, traslados, paquetes), temporadas, tarifas, cupos | Tests de cupo agotado y concurrencia (locks) |
| 2.3 ✔ | `Quotes` | Cotización versionada multi-opción, itinerario día a día, envío y aceptación en línea | Tests de versión inmutable y vencimiento configurable |
| 2.4 ✔ | `Bookings` | Expediente, ítems, pasajeros por ítem, máquina de estados, plazos, cancelaciones con penalidad, saga con compensación (proveedores manuales primero) | Tests de transiciones válidas/ inválidas, idempotencia, compensación |
| 2.5 ✔ | `Documents` | Vouchers, itinerarios y cotizaciones en PDF con la marca de la agencia | Snapshot tests del contenido del PDF |
| 2.6 ✔ | `Integrations` + `Search` | Puerto por producto + adaptadores **Duffel (vuelos) y LiteAPI (hoteles)** en modo de prueba (ADR-0006; Amadeus Self-Service cerró), búsqueda unificada con caché, circuit breaker | Contract tests con fixtures grabados; proveedor caído → degradación controlada |

### Fase 3 — Cobrar, facturar y operar · `v0.4.0`

| # | Módulo | Entregables | Verificación clave |
|---|---|---|---|
| 3.1 ✔ | `Payments` | Links de pago tokenizados, abonos/cuotas, reembolsos, webhooks firmados | Tests de firma e idempotencia de webhooks; nunca PAN/CVV |
| 3.2 ✔ | `Finance` | CxC, CxP, liquidación a proveedores, caja por sucursal, conciliación, rentabilidad por expediente | Cuadre de saldos en tests de escenario |
| 3.3 ✔ | `Invoicing` | Facturas internas, notas crédito/débito, mandato vs. ingreso propio, puerto `EInvoicingProvider` (Null) | Tests de numeración consecutiva y resolución |
| 3.4 | `Compliance` | RNT, pólizas, consentimientos, solicitudes de titulares, calendario de obligaciones | Tests de alertas de vencimiento |
| 3.5 | `Operations` | Salidas, manifiestos, guías, vehículos, incidencias | Tests de capacidad y cierre de salida |
| 3.6 ✔ | `Communications` | Correo + WhatsApp, plantillas, bandeja por expediente | Tests con fakes de canal y colas |
| 3.7 ◐ | `Portal` | Portal del viajero "Mi viaje" y tienda B2C | Feature + Livewire tests; viajero solo ve lo suyo |
| 3.8 ✔ | `Reports` | Dashboards por rol: ventas, margen, plazos, cartera | Tests de KPIs con datasets conocidos |

### Fase 4 — Ampliar · `v0.5.0`

Amadeus autos, traslados, tours y actividades; otros proveedores; `Corporate` (políticas, aprobaciones, SLA), `Groups`, `AfterSales`, `Expenses`, `Staff` (comisiones de vendedores), portal B2B y API pública `/api/v1` con OpenAPI 3.1.

### Fase 5 — Diferenciar · `v1.0.0`

`Marketing`, fidelización, monitor de precios (rebooking), asistente IA con aprobación humana, requisitos de viaje por destino, huella de carbono. Cada función con su KPI y feature flag.

## 3. Requisitos no funcionales transversales

- **Rendimiento:** p95 < 300 ms en pantallas del backoffice; búsquedas con proveedores asíncronas y cacheadas; listados paginados sin N+1.
- **Escalabilidad:** colas Redis + Horizon, workers por cola, caché con claves con nombre, jobs idempotentes.
- **Seguridad:** OWASP Top 10:2025, PCI DSS SAQ-A, Ley 1581/GDPR, 2FA, auditoría.
- **Mantenibilidad:** límites de módulo verificados por arch tests, ADR para cada decisión de arquitectura, cero valores quemados.
- **Observabilidad:** logs JSON estructurados, Pulse, Sentry, health checks.

## 4. Prerrequisitos del entorno (bloquean la Fase 0)

| Tema | Estado actual | Acción |
|---|---|---|
| MariaDB | 10.4.32 (XAMPP) | Solo SQL compatible con MySQL 8; la CI prueba contra MySQL 8 |
| PHP | 8.2.12 (XAMPP), mínimo soportado por Laravel 12 | Sin sintaxis exclusiva de 8.3+ (constantes tipadas, `#[Override]`, `json_validate`) |
| GitHub CLI | Instalado | PRs y estado de CI desde la terminal |
| Redis | No verificado | En local se usa el driver `database`; Redis solo en CI/producción |
| Node.js | No verificado | Necesario para Vite/Tailwind v4 |
