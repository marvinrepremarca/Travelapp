---
description: Ejecuta la Definition of Done completa (formato, análisis estático, tests, cobertura, auditoría de dependencias) y corrige lo que falle.
---

1. `vendor/bin/pint --dirty`
2. `vendor/bin/phpstan analyse --memory-limit=1G`
3. `vendor/bin/rector --dry-run` (solo reporta)
4. `php artisan test --parallel --coverage --min=90`
5. `composer audit` y `npm audit --omit=dev` si cambiaron dependencias.
6. En los archivos modificados busca restos: `dd(`, `dump(`, `ray(`, `var_dump`, `console.log`, código comentado, `float` en dinero, `env(` fuera de `config/`, `withoutGlobalScope` sin comentario.
7. Si algo falla, corrígelo y repite. No agregues entradas al baseline, no uses `@phpstan-ignore` sin justificación ni bajes el umbral.
8. Recorre la Definition of Done de `CLAUDE.md` §6.

Salida (solo esto):
```
Pint ✔ | PHPStan ✔ | Rector ✔ | Tests ✔ (N) | Cobertura XX.X % | Audit ✔
DoD: ✔ completa  — o —  ✖ pendiente: <ítem> (<motivo>)
```
