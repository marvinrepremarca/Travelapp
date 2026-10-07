# Changelog — Operations

## Fase 3.5 (salidas, manifiestos, guías y vehículos)

### Agregado
- **Guías y vehículos** (crear/editar con formulario validado): guía con teléfono, idiomas y tarjeta profesional; vehículo con placa única y capacidad.
- **Salidas de los próximos N días ⚙** con ocupación y alerta de lo que falta (sin guía / sin vehículo).
- **Asignación** de guía y vehículo por salida: sin choques de horario (inicio local en el destino + duración del producto) y con capacidad suficiente para los pasajeros.
- **Manifiesto** de pasajeros confirmados (expediente, edad, nacionalidad, pasaporte enmascarado, contacto) en pantalla y PDF.
- Permiso nuevo *Gestionar la operación* (roles Operaciones, Producto y Dueño). Contratos `DepartureSchedule` (Catalog) y `DepartureManifests` (Bookings).
