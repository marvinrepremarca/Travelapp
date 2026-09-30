# Changelog — Audit

## Fase 1.3

### Agregado
- **Pantalla de auditoría** (permiso `audit.view`) con tres pestañas: cambios (valor anterior → nuevo, filtro por módulo), eventos de seguridad y accesos a datos sensibles; filtros por fechas en la zona horaria de la agencia y paginación.
- **Eventos de seguridad:** inicio y cierre de sesión, intentos fallidos, bloqueos por intentos, restablecimiento de contraseña y cambios/fallos de 2FA. Se guardan en la bitácora `security` y en el log JSON `security`; nunca incluyen contraseñas ni códigos.
- **Accesos a datos sensibles:** contrato `SensitiveDataAccessRecorder` para registrar quién consultó, exportó o descargó un dato personal (sin guardar el dato). Registros inmutables.
- Nombres de bitácora centralizados en el enum `AuditLogName`.
- Retención configurable (`AUDIT_RETENTION_DAYS`); nada se borra automáticamente mientras no se programe `activitylog:clean`.
