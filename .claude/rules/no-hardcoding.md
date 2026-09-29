# Reglas: cero valores quemados (siempre activas, backend y frontend)

Prohibido escribir directamente en el código cualquier valor con significado de negocio, de configuración o de presentación. Cada valor tiene **un único lugar** donde se define y se referencia por nombre.

<donde_vive_cada_valor>
| Tipo de valor | Dónde se define | Ejemplo de uso |
|---|---|---|
| Estados, tipos, canales, roles, permisos, colas, monedas, tipos de documento | `enum` respaldado de PHP | `BookingStatus::Confirmed` |
| Parámetros de negocio ⚙ (plazos, porcentajes, límites, días de anticipación, reintentos, TTL) | `config/travel.php` (default) + tabla `settings` (editable) vía `AppSettings` | `$settings->quoteValidityHours()` |
| Parámetros técnicos (timeouts, tamaños de página, límites de archivo, rate limits) | `config/*.php` leyendo `env()` solo ahí | `config('suppliers.amadeus.timeout_seconds')` |
| Credenciales, URLs de servicios, claves | `.env` → `config/*.php` (nunca en código) | `config('services.amadeus.base_url')` |
| Constantes técnicas intrínsecas de una clase | `private const` con nombre expresivo | `private const MINUTES_PER_HOUR = 60;` |
| Textos visibles, mensajes de error, asuntos de correo | `lang/es/<modulo>.php` | `__('bookings.item_cancelled')` |
| Nombres de rutas, eventos de broadcast, claves de caché | Constantes/enum o métodos que las construyen | `CacheKey::searchResults($hash)` |
| Colores, fuentes, espaciados, radios, sombras, z-index, breakpoints, duraciones | `resources/css/tokens.css` | `bg-brand p-md text-body` |
| Opciones de selects y catálogos | Enum `cases()` o tablas de referencia | `@foreach (BoardType::cases() as $type)` |
| Datos de prueba | Factories y datasets | `Booking::factory()->confirmed()` |
</donde_vive_cada_valor>

<prohibido>
- `if ($booking->status === 'confirmed')`, `where('type', 'hotel')`, `->onQueue('critical')` → usa enums.
- `if ($days < 3)`, `* 0.19`, `->addHours(72)`, `->paginate(25)`, `sleep(5)`, `timeout(10)` → usa config/settings o constante con nombre.
- URLs, correos, teléfonos, NIT, nombres de la agencia o de proveedores escritos en código o en vistas.
- En vistas: textos literales, `#1e40af`, `style="margin:12px"`, `p-[13px]`, `text-[15px]`, `z-50` sin token, `w-[327px]`.
- En JS: `setTimeout(fn, 3000)`, `'/api/v1/bookings'` literal (usa `route()` expuesto con `@js`), mensajes sin traducción.
- Valores "temporales" con `// TODO cambiar`.
</prohibido>

<permitido>
- `0`, `1`, `-1`, `''`, `[]`, `true/false` como identidades o índices obvios; `100` al convertir porcentajes dentro de un value object dedicado.
- Nombres de columnas y relaciones en Eloquent/migraciones; claves de arrays de validación; namespaces.
- Literales en tests cuando **son el dato del escenario** (`'2026-10-12 23:59'` en un dataset con nombre).
</permitido>

Verificación: `code-reviewer` lo revisa en cada cambio; `/verificar` busca literales sospechosos en los archivos modificados; Pint/Larastan/Rector con reglas de strings y números mágicos cuando estén aprobadas.
