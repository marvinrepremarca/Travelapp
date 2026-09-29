# 0002. Una sola agencia con sucursales (sin multi-tenancy)

- Estado: Aceptado
- Fecha: 2026-09-29

## Contexto
El sistema es el sistema de información interno de **una** agencia de viajes: gestiona de forma modular todos sus procesos administrativos y de gestión de viajes (comercial, reservas, operación, finanzas, facturación, talento, calidad) e integra plataformas externas como Amadeus. La agencia tiene sucursales o puntos de venta, asesores y varios canales (backoffice, portal B2C, portal del viajero, portal B2B de agencias aliadas y portal corporativo). No se venderá como SaaS a otras agencias.

## Opciones consideradas
1. **Multi-tenancy (`agency_id` en todas las tablas)**: complejidad y riesgo de fuga en cada consulta sin necesidad real (YAGNI).
2. **Una sola agencia con sucursales y alcance de visibilidad por rol**: simple y fiel al negocio.

## Decisión
Opción 2.
- Datos de la agencia (razón social, NIT, RNT, marca, configuración) en el módulo `Organization`.
- Tablas operativas con `branch_id` y responsable (`owner_id`).
- Cada rol tiene un **alcance**: `own`, `branch` o `all`, aplicado con el trait `HasVisibilityScope` y verificado en Policies. Fuera del alcance → 404.
- Usuarios de portal (viajero, empresa cliente, agencia aliada) solo ven sus propios expedientes.

## Consecuencias
- Consultas, caché y jobs más simples; reportes consolidados y por sucursal inmediatos.
- Convertirlo en SaaS requeriría un ADR nuevo y una migración para agregar tenant; los límites modulares lo facilitan.
