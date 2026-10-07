# Changelog — Portal

## Fase 3.7 (portal del viajero "Mi viaje")

### Agregado
- **Enlace mágico** firmado y con vencimiento ⚙ (sin contraseña): se envía por WhatsApp al titular cuando el expediente queda confirmado; con *Acceder a Mi viaje* se pide uno nuevo con número de expediente + documento del titular (respuesta siempre igual y límite de intentos).
- **Mi viaje**: estado de cuenta (total, pagado, saldo, fecha límite) con **pago del saldo en línea** (reutiliza un link vigente), itinerario día a día con códigos de confirmación y descarga del itinerario y los vouchers en PDF con enlaces firmados.
- Solo contratos de otros módulos: `TravelerTrips` (Bookings), `CustomerPayments` (Payments), `CustomerNotices` (Communications) y `CustomerContacts::documentMatches` (CRM).
