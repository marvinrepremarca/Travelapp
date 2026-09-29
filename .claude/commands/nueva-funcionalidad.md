---
description: Implementa una funcionalidad de negocio de punta a punta (especificación, diseño, código, tests, revisión y verificación).
argument-hint: <descripción de la funcionalidad>
---

Funcionalidad: **$ARGUMENTS**

## 1. Entender
- Carga `travel-domain` y las skills técnicas pertinentes (tabla de `CLAUDE.md` §5).
- Si la funcionalidad tiene reglas de negocio no documentadas, usa el agente `travel-domain-analyst` para obtener historias, reglas ⚙, estados y criterios de aceptación. Lleva sus **preguntas abiertas** al usuario en un solo mensaje y espera respuesta.

## 2. Diseñar (muestra el plan antes de codificar si toca más de ~3 archivos)
- Módulo(s) afectados y comunicación entre ellos.
- Modelo de datos (tablas, índices, datos sensibles).
- Actions, eventos, jobs, policies y permisos.
- Pantallas/endpoints con sus estados.
- Riesgos: concurrencia, dinero, zonas horarias, proveedores.

## 3. Construir en pasos verticales
Migración → modelo + factory → Action + tests unit/feature → UI/API + tests → notificaciones/jobs. Ejecuta los tests afectados en cada paso.

## 4. Revisar
Lanza en paralelo `code-reviewer` y, si toca auth/pagos/datos personales/integraciones, `security-auditor`; si hay UI, `ux-reviewer`. Corrige los 🔴 y 🟠.

## 5. Verificar
Ejecuta `/verificar`. Actualiza la referencia de `travel-domain` con las reglas nuevas decididas.

Reporta en el formato de `CLAUDE.md` §1.
