# TravelApp

Sistema de gestión integral para una agencia de viajes y turismo: CRM, cotizaciones, reservas multi-proveedor, producto propio, operación en destino, cobros, finanzas, facturación, mensajería por WhatsApp, cumplimiento y reportes. Organizado en **capacidades de negocio** que se encienden o apagan por separado.

Stack: PHP 8.2+, Laravel 12, MySQL 8, Redis, Livewire 3, Alpine.js, Tailwind CSS v4, Pest 3.

## Documentación

| Documento | Para quién | Contenido |
|---|---|---|
| [Instalación y despliegue](docs/INSTALACION-Y-DESPLIEGUE.md) | Tecnología | Requisitos, instalación local, `.env`, tareas programadas, colas, despliegue y lista de chequeo de producción |
| [Capacidades del sistema](docs/CAPACIDADES.md) | Tecnología y gerencia | **Activar y desactivar módulos**, dependencias, qué pasa al apagar, cero pérdida de información |
| [Roles, permisos y seguridad](docs/ROLES-Y-PERMISOS.md) | Gerencia y administración | Roles, permisos por área, usuarios, contraseñas temporales, 2FA, auditoría |
| [Flujos del negocio](docs/FLUJOS-DEL-NEGOCIO.md) | Todo el equipo | Lead → cotización → expediente → cobro → factura, estados, aprobaciones, catálogo, WhatsApp |
| [Pruebas manuales](docs/PRUEBAS-MANUALES.md) | Pruebas | Usuarios de demostración y guion de pruebas por módulo |
| [Guía de desarrollo](docs/DESARROLLO.md) | Desarrollo | Arquitectura, reglas, componentes de interfaz, calidad y flujo de trabajo |
| [Plan por fases](docs/ROADMAP.md) | Gerencia | Fases del proyecto |
| [Decisiones de arquitectura](.claude/adr/) | Desarrollo | ADR-0001 a ADR-0007 |

## Inicio rápido (local)

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Abre `http://127.0.0.1:8000/travelapp` (la carpeta la define `APP_PATH_PREFIX`; vacía = raíz del dominio). Detalle en [Instalación y despliegue](docs/INSTALACION-Y-DESPLIEGUE.md).
