---
paths:
  - "tests/**/*.php"
  - "phpunit.xml"
---

# Reglas: tests (Pest)

- `it('confirms the booking when every item is confirmed', ...)`: inglés, presente, comportamiento. AAA separado por líneas en blanco; un comportamiento por test.
- Datos con factories y states; nunca `DB::table()` a mano.
- Datasets para matrices: roles × permisos, transiciones de estado, penalidad × días de anticipación, moneda × redondeo.
- Tiempo con `$this->travelTo()` / `freezeTime()`; nunca `sleep()`. Prueba zonas horarias y cambios de horario (DST) en fechas de servicio.
- Fakes en los límites: `Http::fake()` con **fixtures reales anonimizados** en `tests/Fixtures/Suppliers/<Proveedor>/`, `Queue::fake()`, `Notification::fake()`, `Storage::fake()`. **Nunca** llames a un proveedor ni a una pasarela reales desde la suite.
- Cada endpoint/componente: invitado → login, sin permiso → 403, fuera de alcance (otro asesor u otra sucursal, otro viajero) → 404, validación → errores, caso feliz → OK.
- Cada adaptador de proveedor se prueba con: OK, sin disponibilidad, precio cambiado, timeout, 5xx, respuesta malformada.
- No debilites ni borres un test para que pase; si está mal, explica por qué antes de cambiarlo.
