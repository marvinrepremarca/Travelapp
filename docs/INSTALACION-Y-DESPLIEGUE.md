# Instalación, configuración y despliegue

## 1. Requisitos

| Componente | Versión |
|---|---|
| PHP | 8.2+ con extensiones `intl`, `mbstring`, `pdo_mysql`, `bcmath`, `gd`, `zip`, `opcache` |
| Base de datos | MySQL 8 (producción) · MariaDB 10.4+ (XAMPP local) |
| Node.js | 20+ (solo para compilar CSS/JS) |
| Composer | 2.x |
| Redis | Recomendado en producción (caché, sesiones y colas) |

## 2. Instalación local (Windows + XAMPP)

```bash
composer install
npm install && npm run build
cp .env.example .env          # luego edita la BD y APP_URL
php artisan key:generate
php artisan migrate
php artisan db:seed            # roles, permisos y datos de referencia
php artisan db:seed --class=DemoSeeder   # opcional: datos y usuarios de demostración
php artisan storage:link
php artisan serve
```

En Windows usa `C:\xampp\php\php.exe artisan …` si `php` no está en el PATH.

Abre `http://127.0.0.1:8000/travelapp`. La carpeta la define `APP_PATH_PREFIX` (vacía = raíz del dominio). Si publicas bajo una carpeta en Apache, define también `ASSET_URL=/<carpeta>`.

Usuarios de demostración y guion de pruebas: [PRUEBAS-MANUALES.md](PRUEBAS-MANUALES.md).

## 3. Qué ejecutar cuando cambias algo

| Cambio | Comando |
|---|---|
| Vistas Blade, PHP, textos de `lang/es` | Nada: recarga el navegador. Si no se ve: `php artisan optimize:clear` |
| Clases de Tailwind, `resources/css` (tokens) o `resources/js` | `npm run build` (o `npm run dev` mientras trabajas) |
| `.env` o `config/*.php` | `php artisan config:clear` |
| Rutas | `php artisan route:clear` |
| Migraciones nuevas | `php artisan migrate` |
| Dependencias | `composer install` / `npm install` |

## 4. Configuración (`.env`)

Todas las variables están en `.env.example` con su valor por defecto. Las más importantes:

| Grupo | Variables |
|---|---|
| Aplicación | `APP_URL`, `APP_PATH_PREFIX`, `APP_DEBUG` (false en producción) |
| Agencia | `TRAVEL_AGENCY_TIMEZONE`, `TRAVEL_DEFAULT_CURRENCY`, `TRAVEL_AGENCY_LOCALE` |
| Seguridad | `TRAVEL_TWO_FACTOR_REQUIRED_ROLES` (roles con 2FA obligatorio), `AUTH_LOGIN_ATTEMPTS_PER_MINUTE`, `TRAVEL_PII_HASH_KEY` |
| Capacidades | `CAPABILITY_*_ENABLED` → ver [CAPACIDADES.md](CAPACIDADES.md) |
| Proveedores | `TRAVEL_FLIGHT_PROVIDERS`, `TRAVEL_HOTEL_PROVIDERS`, `TRAVEL_PAYMENT_GATEWAY`, `TRAVEL_E_INVOICING_PROVIDER`, `TRAVEL_WHATSAPP_CHANNEL` (hoy `fake`: simulados) |
| Simulador de WhatsApp | `TRAVEL_WHATSAPP_SIMULATOR`, `TRAVEL_WHATSAPP_SIMULATOR_REQUESTS_PER_MINUTE` |
| Colas y correo | `QUEUE_CONNECTION`, `MAIL_MAILER` y datos SMTP |

Los parámetros de negocio editables (validez de cotizaciones, alcance del asesor, ocultar márgenes…) también se cambian desde **Configurar la agencia → Parámetros** dentro de la aplicación.

## 5. Tareas programadas y colas (obligatorio en producción)

Cron del servidor, cada minuto:
```cron
* * * * * cd /ruta/travelapp && php artisan schedule:run >> /dev/null 2>&1
```

Tareas que ejecuta:

| Tarea | Frecuencia | Qué hace |
|---|---|---|
| `capabilities:catch-up` | cada 5 min | Procesa eventos pendientes de capacidades reencendidas |
| `workflow:send-task-reminders` | cada minuto | Recordatorios de tareas |
| `workflow:expire-approvals` | cada 5 min | Vence aprobaciones sin respuesta |
| `pricing:fetch-official-rate` | 3 veces al día | Trae la TRM oficial |
| `quotes:expire` | cada 15 min | Vence cotizaciones fuera de vigencia |
| `payments:expire-links` | cada 15 min | Vence links de pago |
| `communications:balance-reminders` | diario | Recordatorio de saldo por WhatsApp |

Colas: con `QUEUE_CONNECTION=redis` (o `database`) ejecuta un worker permanente:
```bash
php artisan queue:work --tries=3
```
En local basta `QUEUE_CONNECTION=sync`.

## 6. Despliegue en producción

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader --classmap-authoritative
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class="App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder" --force
php artisan capabilities:status
php artisan optimize
php artisan queue:restart
php artisan up
```

El seeder de roles es seguro de repetir: crea permisos nuevos y **no** pisa lo que el Gerente configuró en Roles y permisos.

### Lista de chequeo de producción

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`
- [ ] **`TRAVEL_WHATSAPP_SIMULATOR=false`**: el simulador es público y permite leer conversaciones por número de teléfono
- [ ] `TRAVEL_UI_SHOWCASE_ENABLED=false`
- [ ] `TRAVEL_TWO_FACTOR_REQUIRED_ROLES` con los roles sensibles (no vacío)
- [ ] Secretos únicos: `APP_KEY`, `TRAVEL_PII_HASH_KEY`, secretos de webhooks
- [ ] HTTPS y `SESSION_SECURE_COOKIE=true`
- [ ] OPcache activo y Xdebug apagado
- [ ] Cron de `schedule:run` y worker de colas activos
- [ ] Copias de seguridad diarias de la base de datos y de `storage/app`
