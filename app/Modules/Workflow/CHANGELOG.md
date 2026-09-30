# Changelog — Workflow

## Fase 1.4

### Agregado
- **Tareas:** crear y asignar (a uno mismo o a usuarios activos dentro del propio alcance), prioridad, vencimiento y recordatorio en la zona horaria de la agencia; terminar, cancelar y reabrir; tareas vencidas marcadas; listado por alcance con filtro por estado y "solo mías".
- **Recordatorios:** `workflow:send-task-reminders` (cada minuto) envía una sola vez una notificación en la aplicación al responsable.
- **Contrato `TaskScheduler`** para que otros módulos creen tareas ligadas a sus registros.
- **Aprobaciones:** tipos descuento, reembolso, anulación de factura, cambio de precio confirmado y exportación de datos personales, cada uno con el permiso de quien aprueba. Nadie decide su propia solicitud; rechazar exige motivo; una sola solicitud pendiente por acción; vencimiento con `workflow:expire-approvals`.
- **Contrato `Approvals`** y eventos `ApprovalRequested` / `ApprovalResolved` (después del commit) para que el módulo dueño ejecute la acción aprobada.
- Permisos por defecto: el director de sucursal aprueba descuentos; finanzas, reembolsos y anulaciones de factura; el gerente, todo.
- Auditoría en la bitácora `workflow`.
