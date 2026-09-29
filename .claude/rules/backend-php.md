---
paths:
  - "app/**/*.php"
  - "routes/**/*.php"
  - "config/**/*.php"
  - "bootstrap/**/*.php"
---

# Reglas: backend PHP

<estructura>
- Todo archivo: `<?php` + línea en blanco + `declare(strict_types=1);`. PSR-4, una clase pública por archivo, PER Coding Style (Pint).
- Clases `final` por defecto. DTOs y value objects: `final readonly class`. Inyección por constructor con promoción de propiedades.
</estructura>

<tipado>
- Tipa parámetros, propiedades y retornos (incluidos `void`, `never`, `static`, nullables, uniones). Evita `mixed`.
- PHPDoc solo para lo que PHP no expresa: genéricos (`Collection<int, Booking>`), array shapes, `list<T>`, `@return BelongsTo<Agency, $this>`.
- Fechas: `CarbonImmutable`. Dinero: `Brick\Money\Money`. Moneda: ISO 4217. Nunca `float` para importes, tasas de cambio ni porcentajes (usa `BigDecimal` o enteros en puntos básicos).
</tipado>

<capas>
| Capa | Responsabilidad | Prohibido |
|---|---|---|
| Controller / componente Livewire | Recibir, autorizar, delegar a una Action, responder | Lógica de negocio, queries complejas, llamadas a proveedores |
| FormRequest / Livewire Form | `authorize()` + `rules()` | Escribir en BD |
| DTO (`Data/`) | Transportar datos tipados e inmutables | Lógica |
| Action | Un caso de uso, único método público `execute()` | Depender de `Request`, sesión o `auth()` global (recibe el actor) |
| Service | Lógica de dominio sin estado (`PriceCalculator`, `CancellationPenaltyCalculator`) | HTTP |
| Query | Consultas de lectura complejas | Escribir |
| Model | Relaciones, casts, scopes simples | Lógica de negocio, efectos secundarios, llamadas externas |
| Policy | Autorización + alcance (own/branch/all) | Lógica de negocio |
| Adapter (`Integrations/`) | Traducir proveedor ↔ dominio | Reglas de negocio, persistencia de dominio |
</capas>

<nombres>
- Nombres completos y del dominio (`$leadTraveler`, `$supplierNetAmount`; no `$p`, `$data2`). Glosario en la skill `travel-domain`.
- Booleanos `is/has/can/should`. Actions `VerboSustantivoAction` (`ConfirmBookingAction`), eventos en pasado (`BookingConfirmed`), jobs imperativos (`SyncHotelContentJob`), excepciones descriptivas (`FareNoLongerAvailable`).
- Sin strings ni números mágicos: ver regla `no-hardcoding.md`.
</nombres>

<clean_code>
- Métodos ≈ 20 líneas, un nivel de abstracción, early return, sin `else` tras `return`. Más de 3-4 parámetros → DTO.
- SOLID: una razón de cambio por clase; depende de interfaces en los límites (módulos, proveedores, pasarelas, facturador electrónico).
- Patrones con propósito: Strategy (reglas de precio, penalidades), State (ciclo de vida de la reserva), Adapter (proveedores), Pipeline (cálculo de precio), Saga (reserva multi-ítem). YAGNI para el resto.
- Comentarios solo para el **porqué**. Nada de código comentado.
</clean_code>

<laravel>
- `config('travel.x')`, nunca `env()` fuera de `config/`.
- Escrituras en varias tablas dentro de `DB::transaction()`; **nunca** una llamada HTTP externa dentro de la transacción.
- `Model::query()` explícito. Sin queries en vistas ni en loops (`with`, `withCount`, `loadMissing`).
- Excepciones de negocio extienden `App\Modules\Shared\Exceptions\BusinessRuleException` con mensaje traducible. Nunca `catch` vacío.
- Autorización con `Gate::authorize()`, Policies o `authorize()` del FormRequest.
- Operaciones concurrentes sobre cupos, pagos o estados: `lockForUpdate()` o `Cache::lock()`, más `idempotency_key` si interviene un tercero.
</laravel>
