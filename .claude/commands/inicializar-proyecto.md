---
description: Configura un proyecto Laravel 12 recién creado con las herramientas de calidad, seguridad, estructura modular y convenciones de este repositorio.
---

Configura el proyecto base siguiendo `CLAUDE.md`. **Precondición:** el esqueleto de Laravel 12 (`laravel new travelapp --pest --database=mysql`, sin starter kit) está en la raíz junto a `.claude/`. Si `composer.json` no existe, detente y repórtalo.

Ejecutar este comando aprueba instalar **solo** las dependencias listadas aquí. Cualquier otra requiere preguntar.

## Pasos
1. **Entorno:** `C:\xampp\php\php.exe -v` (≥ 8.2) con `intl`, `bcmath`, `sodium`, `zip`, `gd`, `pdo_mysql`; `composer -V`; `node -v`; PCOV o Xdebug para cobertura; versión de MySQL/MariaDB. Si algo falta, detente y repórtalo.
2. **Dependencias:**
   - `composer require laravel/fortify laravel/sanctum livewire/livewire spatie/laravel-permission spatie/laravel-activitylog brick/money`
   - `composer require --dev larastan/larastan rector/rector driftingly/rector-laravel`
   - `npm install alpinejs` (Tailwind v4 y Vite ya vienen con Laravel 12).
   - Horizon, Pulse, Sentry y spatie/laravel-pdf se instalan cuando la fase lo requiera (con aprobación).
3. **Calidad:**
   - `pint.json` con preset `per` y reglas `declare_strict_types`, `strict_comparison`, `no_unused_imports`, `ordered_imports`, `final_class`.
   - `phpstan.neon` nivel 8 sobre `app/`, `database/`, `routes/`, `config/`.
   - `rector.php` con sets de PHP 8.2 y Laravel 12 (solo en modo `--dry-run` en CI).
   - Scripts en `composer.json`: `lint`, `analyse`, `refactor:check`, `test`, `test:coverage` (`--parallel --coverage --min=90`), `check` (todos).
4. **Configuración de la app:**
   - `.env.example`: `APP_LOCALE=es`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=es_CO`, `APP_TIMEZONE=UTC`, `QUEUE_CONNECTION=database`, `SESSION_SECURE_COOKIE=true`, `DB_CHARSET=utf8mb4`.
   - `config/app.php` → `timezone` = `UTC` (la zona horaria de la agencia se aplica al presentar).
   - `config/queue.php` → `after_commit => true`.
   - `config/travel.php` con los parámetros ⚙ por defecto de `travel-domain`; `config/suppliers.php` vacío con la estructura del registry.
   - `php artisan lang:publish` y `lang/es/` (validation, auth, passwords, pagination).
   - `AppServiceProvider::boot()` con la configuración base de `laravel-backend`.
   - Canales de log `security` y `suppliers` en formato JSON con processor de redacción.
5. **Estructura modular:** `app/Modules/Shared` (BusinessRuleException, `Tone`, `Money` cast, `HasVisibilityScope`), autoload PSR-4, `app/Modules/Organization` e `app/Modules/Identity` mínimos, `tests/Arch/` con los tests de `modular-architecture`, `tests/Pest.php` con helpers y `Http::preventStrayRequests()`.
6. **Auth:** Fortify (login, reset, 2FA, confirmación de contraseña), roles y permisos base de `travel-domain/references/roles.md` en un seeder idempotente.
7. **Frontend:** `resources/css/app.css` con tokens de `frontend-ui`, layouts `backoffice`, `portal`, `print` y componentes `x-ui` básicos (button, field, input, badge, card, table, empty-state, skeleton).
8. **Docs:** confirma que existen `.claude/adr/0001…0003`.
9. Ejecuta `composer check` hasta que esté en verde.

Reporta en el formato de `CLAUDE.md` §1.
