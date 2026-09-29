# Diferenciadores (valor agregado para agencias y viajeros)

Estas funciones distinguen al sistema de un backoffice genérico. Implementa sobre la base sólida (fases 1-3) y prioriza con el usuario.

## Para el viajero
1. **Itinerario vivo** (portal/PWA "Mi viaje"): línea de tiempo del viaje con vouchers, tiquetes, QR, mapas, puntos de encuentro, contactos de emergencia; disponible **offline** y actualizado en tiempo real ante cambios.
2. **Alertas proactivas:** cambios de vuelo, puerta, retrasos (feed de estado de vuelos), recordatorio de check-in en línea, hora de recogida del traslado reprogramada automáticamente.
3. **Requisitos de viaje** por destino y nacionalidad: visa, pasaporte con vigencia mínima, vacunas, seguro obligatorio, formularios de ingreso; con checklist por pasajero.
4. **Concierge por WhatsApp** conectado al expediente: el asesor ve el historial y el viajero recibe documentos por el mismo canal.
5. **Pago flexible:** abonos, cuotas, pagos divididos entre participantes, links de pago y recordatorios.
6. **Upselling contextual:** ofrecer traslado, seguro, actividades y mejoras de habitación según el destino y las fechas del expediente, antes del viaje y durante.
7. **Post-viaje:** encuesta NPS, reseñas, álbum/recuerdo del viaje, recomendación del próximo destino.

## Para la agencia
1. **Cotizador visual** con itinerario día a día, fotos, mapas, multi-opción y aceptación/pago en línea; seguimiento de apertura de la propuesta (se vio, cuándo, cuántas veces).
2. **Monitor de precios (rebooking):** tras reservar un hotel reembolsable, vigilar si baja el precio y proponer re-reservar para aumentar margen o ahorro al cliente.
3. **Tablero de plazos:** fechas límite de emisión, pago y cancelación gratuita en una sola vista priorizada por riesgo.
4. **Rentabilidad en tiempo real** por expediente mientras se cotiza (margen visible solo para roles autorizados).
5. **Gestión de grupos:** inscripción en línea, rooming list colaborativa, pagos individuales, manifiestos.
6. **Asistente IA** (con aprobación humana): redactar itinerarios desde notas, resumir el historial del cliente, sugerir productos, clasificar correos de proveedores y extraer localizadores. Nunca confirma, cobra ni cancela solo.
7. **Tienda online propia:** portal B2C con el dominio y la marca de la agencia, SEO de productos.
8. **Portal B2B:** la agencia publica su producto propio para que agencias aliadas lo vendan con tarifa neta o comisión.
9. **Huella de carbono** estimada por itinerario y reportes de sostenibilidad para clientes corporativos.
10. **Fidelización:** puntos, niveles y beneficios propios de la agencia.

## Principios para estas funciones
- Medibles: cada una define su KPI (conversión de cotizaciones, ingreso por upsell, NPS, tiempo de respuesta) en `reports-dashboard`.
- Activables por configuración (feature flags).
- Nunca comprometen el principio de "el humano confirma" en acciones con impacto económico.
