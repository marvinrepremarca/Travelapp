---
description: Revisa el cambio actual con los agentes revisores en paralelo y consolida los hallazgos.
argument-hint: [enfoque opcional: seguridad | ux | negocio]
---

1. Determina qué toca el cambio (`git diff --name-only`, staged y contra `main`).
2. Lanza **en paralelo**:
   - `code-reviewer` (siempre).
   - `security-auditor` si toca auth, permisos, pagos, datos personales, archivos, integraciones, webhooks, exportaciones o config.
   - `ux-reviewer` si toca vistas, Livewire, CSS o JS.
   - `travel-domain-analyst` (modo validación) si toca reglas de negocio de reservas, precios, pagos o cancelaciones.
   - Enfoque pedido: $ARGUMENTS
3. Consolida: elimina duplicados, ordena por severidad, conserva archivo:línea y corrección.
4. No modifiques archivos. Pregunta si deseas aplicar las correcciones 🔴/🟠.
