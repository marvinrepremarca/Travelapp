# Changelog — Documents

## Fase 2.5

### Agregado
- Módulo de documentos PDF (spatie/laravel-pdf + DomPDF, ADR-0005) con membrete de la agencia (logo incrustado, NIT, contacto, color de marca) y pie con RNT.
- Contrato `DocumentRenderer` para que cada módulo genere sus PDF con su plantilla.
- Contrato `AgencyLetterhead` en Organization con los datos del membrete.
- **Cotización PDF** (Quotes): versión enviada con opciones, precios de venta e itinerario; descarga interna por alcance y desde el enlace firmado del cliente.
- **Voucher** (Bookings): solo servicios confirmados, con código del proveedor y pasajeros.
- **Itinerario del expediente** (Bookings): servicios vigentes día a día con confirmaciones y pasajeros.
