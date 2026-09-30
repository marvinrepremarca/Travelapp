# TravelApp

Sistema de gestión integral para una agencia de viajes y turismo: CRM, cotizaciones, reservas multi-proveedor (Amadeus), operación, cobros, finanzas, facturación y reportes.

- Plan de trabajo por fases: [docs/ROADMAP.md](docs/ROADMAP.md)
- Convenciones y arquitectura: [.claude/CLAUDE.md](.claude/CLAUDE.md)
- Decisiones de arquitectura: [.claude/adr/](.claude/adr/)

Stack: PHP 8.2+, Laravel 12, MySQL 8, Redis, Livewire 3, Tailwind CSS v4, Pest 3.

## Carpeta de publicación

La aplicación se publica bajo la carpeta definida en `APP_PATH_PREFIX` (hoy `travelapp` → `http://127.0.0.1:8000/travelapp`). Todas las rutas, Fortify y Livewire usan ese prefijo. Déjalo vacío para publicarla en la raíz de un dominio.
