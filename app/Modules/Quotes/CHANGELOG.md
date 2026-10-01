# Changelog — Quotes

## Fase 2.3 (parte A: cotización multi-opción versionada)

### Agregado
- **Cotización** de un cliente del alcance del asesor, con número consecutivo, moneda de venta y canal. Visibilidad por alcance (propias, sucursal, todas); fuera del alcance responde 404.
- **Opciones A, B, C…** (máximo configurable) con ítems de **catálogo propio** (neto por temporada y edad) o **de proveedor con neto manual** (hotel, vuelo…). Venta, margen, impuestos y tasa de cambio los calcula Pricing en el servidor; el desglose queda congelado en el ítem.
- **Envío por versiones**: enviar congela una versión inmutable (opciones, ítems y precios) y fija la vigencia configurable. Una opción vacía no se envía.
- **Nueva versión**: una cotización enviada o vencida vuelve a borrador y recalcula todos los precios con tarifas, reglas y tasas de hoy; las versiones enviadas no cambian.
- **Aceptación registrada por el asesor** (opción de la versión vigente, con nota) solo antes de vencer; si ya venció, queda vencida.
- **Vencimiento automático** cada 15 minutos (`quotes:expire`) y cancelación auditada.
- Eventos `QuoteSent` y `QuoteAccepted` para CRM, notificaciones y reservas.
- El margen se oculta a los asesores salvo permiso o configuración de la agencia.

## Fase 2.3 (parte B: enlace del cliente e itinerario)

### Agregado
- **Enlace firmado** por versión, sin sesión, que caduca con la vigencia; límite de solicitudes por IP ⚙. El asesor lo copia desde la cotización enviada.
- El cliente ve **solo precios de venta** de la versión enviada (nunca neto ni margen), el **itinerario día a día** de cada opción y **acepta una opción** con su nombre y la aceptación de condiciones (canal "Enlace del cliente").
- Un enlace de una versión reemplazada, vencida, cancelada o ya aceptada lo explica y no permite aceptar.
- **Itinerario día a día** también en la ficha interna, con noches y fecha de salida de hoteles.
