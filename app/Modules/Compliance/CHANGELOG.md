# Changelog — Compliance

## Fase 3.4 (cumplimiento legal)

### Agregado
- **Documentos legales** (RNT, pólizas, registro mercantil, licencias) con vigencia; al registrar o renovar se crea la tarea de renovación para el responsable con aviso N días antes ⚙.
- **Calendario de obligaciones** con responsable y periodicidad (única, mensual, trimestral, anual); al cumplir una periódica se programa la siguiente. Tareas con recordatorio en Workflow (`TaskScheduler`).
- **Solicitudes de titulares** (Ley 1581): consulta, rectificación, supresión, revocatoria y reclamo; radicado consecutivo, plazo en días hábiles ⚙ con el calendario de festivos (`HolidayCalendar`), datos del titular cifrados y enmascarados (revelarlos queda auditado), respuesta obligatoria al cerrar.
- Panel con alertas de vencimiento (documentos, obligaciones y solicitudes) y permiso nuevo *Gestionar el cumplimiento legal* (roles Dueño y Finanzas).
