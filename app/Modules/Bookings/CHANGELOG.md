# Changelog — Bookings

## Fase 2.4 (parte A: expediente desde la cotización y confirmación por servicio)

### Agregado
- **Expediente** creado desde la opción aceptada de una cotización (contrato `AcceptedQuotes`), con número consecutivo, del asesor y la sucursal de la cotización. Los precios se copian congelados; una cotización produce un solo expediente.
- **Estado por servicio** con el proveedor: por solicitar → en espera → confirmado (con código) | rechazado → cancelado. Las notas son obligatorias al dejar en espera, rechazar o cancelar. Todo queda auditado.
- **Producto propio**: al confirmar se elige la salida del día y se apartan los cupos (todos los pasajeros ocupan cupo); sin cupo no se confirma. Cancelar devuelve los cupos.
- **Estado del expediente derivado**: en gestión, confirmado, requiere atención (hay rechazados) o cancelado.
- Pantallas de expedientes (alcance propio/sucursal/todo, 404 fuera del alcance) y "Crear expediente" desde la cotización aceptada.

## Fase 2.4 (parte B: pasajeros por servicio)

### Agregado
- **Pasajeros del CRM por servicio**: se eligen viajeros del cliente; deben coincidir en número y tipo (adulto, niño, infante por edad a la fecha del servicio) con lo cotizado, porque el precio se calculó así.
- La edad y el tipo quedan congelados al asignar; la fecha de nacimiento sigue cifrada en el CRM. Cada asignación queda en la auditoría.
- La ficha avisa cuántos servicios vigentes no tienen pasajeros.
