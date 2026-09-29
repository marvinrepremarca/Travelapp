---
description: Crea el esqueleto de un módulo nuevo en app/Modules siguiendo la arquitectura modular.
argument-hint: <NombreModulo en inglés y PascalCase> [descripción breve]
---

Crea el módulo: **$ARGUMENTS**

1. Carga `modular-architecture` y `travel-domain` (revisa `references/modules.md`: responsabilidad, fase y dependencias permitidas). Si el módulo no está en el mapa, propón su lugar y pide confirmación.
2. Si no está claro, pregunta en un solo mensaje: entidades principales, qué expone (Contracts/Events), qué consume, roles y permisos.
3. Crea solo lo necesario:
   - `Providers/<Modulo>ServiceProvider.php` registrado en `bootstrap/providers.php` (rutas, vistas `<modulo>::`, migraciones, policies, bindings de contracts, listeners).
   - `Routes/web.php` (grupo `auth`, nombres `<modulo>.*`) y `Routes/api.php` si aplica.
   - `lang/es/<modulo>.php`.
   - Permisos nuevos en el enum `Permission` y en el seeder de roles.
4. Agrega el módulo a `MODULES` en los arch tests.
5. Actualiza `travel-domain/references/modules.md` si cambió algo. ADR si introduce una dependencia nueva o una excepción a los límites.
6. `composer dump-autoload` y `composer check`.

Reporta en el formato de `CLAUDE.md` §1.
