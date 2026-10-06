# Changelog — Communications

## Fase 3.6 (parte A: chat de WhatsApp para cotizar)

### Agregado
- **Puerto `MessagingChannel`** (WhatsApp Cloud API, Twilio, 360dialog…) con adaptador **simulado** en Integrations; el activo se elige en `.env`. Webhook entrante firmado, idempotente por id de mensaje y procesado en cola; envíos en cola fuera de la transacción.
- **Bot guiado**: saluda y pregunta nombre, destino, fecha de salida, regreso (o solo ida) y número de viajeros, validando cada respuesta; con "asesor" pasa a una persona en cualquier momento.
- **Bandeja de conversaciones** (menú Ventas): por atender, mías, abiertas y cerradas, con actualización automática. El asesor **toma** la conversación (se crea el lead con lo que recogió el bot, canal WhatsApp), responde y la cierra.
- Teléfono cifrado con huella HMAC y enmascarado en pantalla.
- **Simulador de WhatsApp** para la demo: escribir como cliente usa el mismo webhook firmado que el proveedor real.
