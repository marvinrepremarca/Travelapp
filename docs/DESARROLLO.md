# Guía de desarrollo

Para quien modifica el código. Las reglas completas están en [.claude/CLAUDE.md](../.claude/CLAUDE.md) y `.claude/rules/`.

## 1. Arquitectura

- **Monolito modular**: cada módulo en `app/Modules/<Modulo>` con sus rutas, vistas, migraciones, Livewire, Actions, Policies y tests de arquitectura que vigilan sus límites ([ADR-0001](../.claude/adr/0001-monolito-modular.md)).
- Los módulos se comunican por **Contracts** (síncrono, con implementación nula si la capacidad está apagada) o **eventos de integración** (asíncrono, con bandeja de salida persistente). Nunca se tocan tablas ni Actions de otro módulo ([ADR-0007](../.claude/adr/0007-capacidades-activables.md)).
- **Una sola agencia con sucursales**; la visibilidad se controla por rol y alcance ([ADR-0002](../.claude/adr/0002-agencia-unica-con-sucursales.md)).
- Capas: Livewire/Controller (delgado) → validación → DTO → Action (un caso de uso) → Model/Service.

| Núcleo | Capacidades |
|---|---|
| Shared, Organization, Identity, Audit, Workflow, Customers, Suppliers, Pricing, Documents, Integrations | Crm, Quotes + Search, Catalog, Bookings, Operations, Payments, Finance, Invoicing, Communications, Portal, Compliance, Reports |

## 2. Reglas que no se negocian

- Dinero con `Money` (brick/money), nunca `float`; fechas con `CarbonImmutable`, UTC en la BD.
- **Cero valores quemados**: estados y tipos en enums; parámetros en `config/travel.php` / `.env`; textos en `lang/es`; colores, espacios y tamaños en `resources/css/tokens.css`.
- Toda acción autorizada por Policy/permiso y todo listado filtrado por alcance; IDs públicos ULID.
- Toda mutación relevante auditada; datos personales cifrados.
- Ninguna dependencia nueva sin aprobación.

## 3. Interfaz

- Componentes reutilizables en `resources/views/components/ui` (`x-ui.button`, `x-ui.icon-button`, `x-ui.copy-button`, `x-ui.select`, `x-ui.country-select`, `x-ui.currency-select`, `x-ui.customer-picker`, `x-ui.table`, `x-ui.card`…).
- Acciones de fila con `x-ui.icon-button` (icono + `aria-label` + tooltip). Ningún `<select>` de formulario trae valor por defecto.
- **Ojo**: dentro de los atributos de un componente Blade (`<x-ui.button x-on:click="…">`) las directivas como `@js` no se compilan; pasa los valores por `x-data` de un elemento HTML.

## 4. Calidad

```bash
composer check          # Pint + Rector + PHPStan nivel 8 + Pest (con arquitectura) + auditoría
composer test           # solo tests
composer test:coverage  # cobertura (mínimo 90 % en app/Modules)
```

- Cada pantalla nueva entra a `tests/Feature/Shared/PerformanceBudgetTest.php` (≤ 15 consultas, cero repetidas).
- La CI (GitHub Actions) corre lo mismo contra MySQL 8; un PR se fusiona solo con la CI en verde.

## 5. Flujo de trabajo

1. Rama desde `main` (`feat/...`, `fix/...`).
2. Cambios pequeños y verticales con sus tests.
3. `composer check` en verde.
4. PR hacia `main`; fusión con CI en verde (squash).
5. Si cambia la arquitectura, un ADR nuevo en `.claude/adr/`.
