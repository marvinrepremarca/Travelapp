# Changelog — Portal

## Fase 3.7 (parte B: tienda B2C)

### Agregado
- **Tienda** pública (`/shop`): productos propios activos con salidas abiertas y cupo en los próximos N días ⚙, con precio de venta "desde" recalculado en el servidor (canal en línea).
- **Solicitud de reserva**: salida, personas, contacto y autorización de tratamiento de datos (Ley 1581) → lead del canal En línea para el asesor configurado ⚙ (o el primer asesor activo), con la salida, los cupos y el valor estimado. Límite de solicitudes por hora ⚙. Los cupos no se apartan hasta que el asesor confirma.
- Contrato nuevo `ShopCatalog` (Catalog).

## Fase 3.7 (portal del viajero "Mi viaje")

### Agregado
- **Enlace mágico** firmado y con vencimiento ⚙ (sin contraseña): se envía por WhatsApp al titular cuando el expediente queda confirmado; con *Acceder a Mi viaje* se pide uno nuevo con número de expediente + documento del titular (respuesta siempre igual y límite de intentos).
- **Mi viaje**: estado de cuenta (total, pagado, saldo, fecha límite) con **pago del saldo en línea** (reutiliza un link vigente), itinerario día a día con códigos de confirmación y descarga del itinerario y los vouchers en PDF con enlaces firmados.
- Solo contratos de otros módulos: `TravelerTrips` (Bookings), `CustomerPayments` (Payments), `CustomerNotices` (Communications) y `CustomerContacts::documentMatches` (CRM).
