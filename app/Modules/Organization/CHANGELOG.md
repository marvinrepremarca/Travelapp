# Changelog — Organization

## Fase 1.1

### Agregado
- **Datos de la agencia:** razón social, nombre comercial, NIT con dígito de verificación DIAN (se valida y se calcula en el servidor), RNT con alerta de vencimiento, contacto, logo y colores de marca con contraste WCAG AA. La marca se aplica en toda la interfaz.
- **Sucursales:** alta, edición, activación y desactivación (nunca se borran), responsable con rol de director de sucursal, búsqueda y filtro por estado.
- **Festivos:** calendario nacional de Colombia calculado automáticamente (fijos, Ley Emiliani y dependientes de la Pascua) con ajustes de la agencia: días no hábiles propios o festivos en los que se trabaja. Contrato `HolidayCalendar` con días hábiles.
- **Parámetros:** tabla `settings` editable desde la pantalla "Parámetros", con valores por defecto de `config/travel.php`, validación y auditoría. Contrato `AppSettings`.
- Toda modificación queda en la bitácora de auditoría (`log_name = organization`).
