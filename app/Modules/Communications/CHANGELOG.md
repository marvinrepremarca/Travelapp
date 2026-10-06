# Changelog — Communications

## Fase 3.6 (parte A: chat de WhatsApp para cotizar)

### Agregado
- **Puerto `MessagingChannel`** (WhatsApp Cloud API, Twilio, 360dialog…) con adaptador **simulado** en Integrations; el activo se elige en `.env`. Webhook entrante firmado, idempotente por id de mensaje y procesado en cola; envíos en cola fuera de la transacción.
- **Bot guiado**: saluda y pregunta nombre, destino, fecha de salida, regreso (o solo ida) y número de viajeros, validando cada respuesta; con "asesor" pasa a una persona en cualquier momento.
- **Bandeja de conversaciones** (menú Ventas): por atender, mías, abiertas y cerradas, con actualización automática. El asesor **toma** la conversación (se crea el lead con lo que recogió el bot, canal WhatsApp), responde y la cierra.
- Teléfono cifrado con huella HMAC y enmascarado en pantalla.
- **Simulador de WhatsApp** para la demo: escribir como cliente usa el mismo webhook firmado que el proveedor real.

## Fase 3.6 (parte B: avisos automáticos por WhatsApp)

### Agregado
- Avisos con plantilla (listas para aprobar en WhatsApp real): **cotización enviada** (enlace para verla y aceptarla), **link de pago**, **abono recibido** (efectivo, transferencia validada o pago en línea confirmado) y **recordatorio de saldo** N días ⚙ antes de la fecha límite (proceso diario a la hora ⚙).
- Solo a clientes con teléfono y consentimiento vigente de tratamiento de datos (contrato `CustomerContacts` de CRM). Un aviso por evento (clave de deduplicación): reintentos y el proceso diario no repiten mensajes.
- El aviso va en la conversación abierta del cliente o abre una a cargo del asesor responsable, para que la respuesta del cliente le llegue a él.
- Eventos nuevos `PaymentLinkCreated` y `PaymentReceived` (Payments) y contratos `SentQuotes` (Quotes), `UpcomingBalances` (Payments) y `BookingAccounts::startingOn` (Bookings).
