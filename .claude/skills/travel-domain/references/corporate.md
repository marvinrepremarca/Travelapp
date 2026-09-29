# Viajes de negocios (TMC)

## Cuenta corporativa
- Empresa cliente con NIT, condiciones de crédito (cupo y plazo), contactos (travel manager, aprobadores, finanzas), centros de costo, proyectos y campos de reporte obligatorios ⚙ (ej. número de orden de compra).
- Perfiles de viajero: datos de documentos, preferencias (silla, comida), programas de lealtad, tarjeta corporativa tokenizada.

## Política de viajes (`TravelPolicy`)
- Reglas por nivel/cargo: cabina permitida por duración de vuelo, tope por noche de hotel por ciudad, categoría máxima de auto, anticipación mínima de compra, tarifa más baja lógica (LLF) dentro de ±X USD/horas.
- Evaluación: cada oferta muestra "dentro de política" o "fuera de política" con el motivo; fuera de política exige **código de justificación** ⚙.
- Proveedores preferidos y tarifas negociadas corporativas (códigos de descuento) se aplican y se destacan.

## Aprobaciones
- Flujos configurables: sin aprobación, solo fuera de política, siempre; uno o varios niveles; por monto.
- Aprobación por correo/WhatsApp con enlace firmado de un solo uso, con expiración (anterior a la fecha límite de emisión). Si vence, escalar ⚙.
- Toda aprobación queda auditada.

## Duty of care
- Mapa y lista de "¿quién está dónde ahora?" desde los itinerarios activos.
- Alertas por evento en destino (manuales o de un feed externo) → contactar a los viajeros afectados y registrar su confirmación de estado.
- Contacto de emergencia 24/7 visible en el portal del viajero.

## Control y reporting
- Tiquetes no usados rastreados como crédito con su vencimiento; sugerir su uso en nuevas reservas del mismo viajero y aerolínea.
- Reportes: gasto por centro de costo/proyecto/viajero, ahorro vs. tarifa pública, cumplimiento de política, anticipación de compra, huella de carbono.
- Facturación consolidada por período (extracto mensual) además de la factura por transacción.

## Acuerdos de nivel de servicio (SLA) corporativos

Cada cuenta corporativa puede tener un **contrato de servicio** (`ServiceAgreement`) con SLA medibles. Los números concretos (tiempos, porcentajes, penalidades) **se pactan por contrato**: el sistema no los fija, solo trae plantillas con defaults ⚙.

### Contrato (`ServiceAgreement`)
- Pertenece a una `CorporateAccount`; tiene vigencia (`starts_on`, `ends_on`), versión, estado (`draft` → `active` → `expired` / `terminated`) y documento firmado adjunto.
- Solo un contrato `active` por cuenta y período; renovar = nueva versión. Un cambio de SLA durante la vigencia se hace con **otrosí** (nueva versión con fecha efectiva); los casos ya abiertos se miden con la versión vigente cuando se abrieron.
- Define: **calendario de atención** (horario hábil + zona horaria + festivos del país del contrato, o 24/7), canales cubiertos (teléfono, WhatsApp, correo, portal), idiomas, gestor de cuenta asignado y matriz de escalamiento.
- Toda creación, cambio o terminación queda auditada con el actor y el motivo.
- Los turnos de guardia se registran como `OnCallShift`; la liquidación del período, como `SlaSettlement` (`draft` → `in_review` → `approved` → `invoiced`, o `disputed`).

### Indicadores de SLA (`SlaMetric`, enum)
Cada contrato elige qué indicadores aplican y con qué objetivo ⚙:

| Indicador | Qué mide | Inicio del reloj → fin |
|---|---|---|
| `first_response_time` | Tiempo a primera respuesta humana | Solicitud recibida → primera respuesta de un asesor (las respuestas automáticas no cuentan) |
| `quote_delivery_time` | Tiempo de entrega de cotización/opciones | Solicitud completa → cotización enviada |
| `booking_confirmation_time` | Tiempo de confirmación | Aprobación del viajero/aprobador → reserva confirmada y documentos enviados |
| `emergency_response_time` | Respuesta en emergencia 24/7 | Contacto por línea de emergencia → asesor atendiendo |
| `disruption_rebooking_time` | Reacomodación ante contingencia (retraso, cancelación, clima) | Alerta de disrupción → alternativa ofrecida al viajero |
| `resolution_time` | Resolución de PQRS/incidencias | Caso abierto → caso resuelto con aceptación del cliente |
| `invoice_delivery_time` | Entrega de factura/extracto | Servicio emitido o cierre de período → factura o extracto entregado |
| `booking_accuracy` | Exactitud | % de reservas sin error atribuible a la agencia (nombre, fecha, tarifa) |
| `policy_compliance` | Cumplimiento de política | % de reservas dentro de la política o con justificación registrada |
| `availability_24_7` | Cobertura 24/7 | % de contactos de emergencia atendidos dentro del objetivo |

- Objetivo por indicador: **umbral** (ej. ≤ X minutos) + **porcentaje de cumplimiento** esperado en el período (ej. 95 % de los casos) ⚙.
- Los objetivos pueden variar por **prioridad** (`SlaPriority`: `critical`, `high`, `normal`, `low`) y por canal. La prioridad se asigna con reglas ⚙ (ej. viaje en las próximas 24 h o viajero en ruta = `critical`; VIP según la cuenta); un cambio manual de prioridad exige motivo y queda auditado.

### Medición (reloj de SLA)
- Cada solicitud sujeta a SLA genera un **reloj** (`SlaTimer`) por indicador, con instantes UTC: `started_at`, `paused_at`/`resumed_at`, `due_at`, `met_at` / `breached_at`.
- El reloj corre en el **calendario del contrato**: si es horario hábil, fuera de ese horario no suma; en emergencias y disrupciones corre siempre 24/7.
- **Pausa** solo por causas imputables al cliente (esperando información, aprobación o pago del cliente) y con motivo registrado; nunca por esperar al proveedor, salvo que el contrato lo excluya expresamente.
- Estados del reloj: `running` → `paused` → `running` … → `met` | `breached`. Una alerta ⚙ avisa al asesor y al supervisor antes de vencer (ej. al 75 % del tiempo) y el escalamiento sigue la matriz del contrato.
- La medición es **automática a partir de eventos** del sistema (mensaje recibido, respuesta enviada, cotización enviada, reserva confirmada), no de datos ingresados a mano. Un ajuste manual (ej. evento ocurrido fuera del sistema) exige permiso, evidencia y motivo, y queda auditado.
- **Exclusiones** pactadas (fuerza mayor, caída comprobada del GDS o de la aerolínea, información incompleta del cliente) se marcan en el caso con evidencia; el caso queda "excluido" del cálculo, no se borra.

### Soporte 24/7 medible
- Línea de emergencia y canal de WhatsApp 24/7 con **turnos de guardia** (asesor de guardia por franja y zona horaria) registrados en el sistema; debe haber cobertura sin huecos: un turno sin asignar genera alerta ⚙.
- Todo contacto fuera de horario crea un caso con reloj de `emergency_response_time`; si nadie lo toma en el tiempo ⚙, escala automáticamente al siguiente nivel.
- Las contingencias detectadas por el estado de vuelos (retraso, cancelación, cambio de puerta) abren caso proactivo con reloj de `disruption_rebooking_time`, aunque el viajero no haya escrito.

### Penalidades y bonificaciones
- Por indicador y período: si el cumplimiento real queda por debajo del objetivo, se aplica la penalidad pactada: **crédito de servicio** (% del fee de gestión del período), descuento en la próxima factura, o monto fijo ⚙. Opcionalmente, bonificación por sobrecumplimiento.
- Tope máximo de penalidad por período (ej. % del fee mensual) ⚙. Las penalidades se calculan con `Money` sobre los **fees de la agencia**, nunca sobre el neto del proveedor.
- La penalidad **no se aplica automáticamente**: el cierre del período genera una **liquidación de SLA** en borrador → revisión del gestor de cuenta → aprobación (doble aprobación según `roles.md`) → se emite una **nota crédito** o descuento en `Invoicing`. Todo auditado.
- Disputa del cliente: la liquidación pasa a `disputed`, se revisan las evidencias de los casos y se re-liquida con motivo.

### Reportes de SLA
- Tablero por cuenta y período: cumplimiento por indicador vs. objetivo, tendencia, casos incumplidos con causa, casos excluidos, penalidades aplicadas.
- **Informe mensual de servicio** (QBR/MBR) exportable en PDF para el cliente, con la misma fuente de datos del tablero (sin cálculos paralelos).
- En el portal corporativo el travel manager ve el cumplimiento de su propia cuenta; nunca datos de otras cuentas.

### Casos borde
- Solicitud recibida por un canal no cubierto por el contrato: se atiende, pero no computa SLA (queda marcado).
- Solicitud incompleta: el reloj de cotización arranca cuando la solicitud está completa; el de primera respuesta arranca al recibirla.
- Contrato vence con casos abiertos: se miden con el contrato con el que se abrieron.
- Cambio de horario de verano o viajero en otra zona horaria: el reloj siempre en UTC; el calendario hábil usa la zona del contrato.
- Festivo del país del contrato: no computa en SLA de horario hábil; sí en 24/7.

**Módulo:** `Corporate` (contratos, indicadores, relojes, liquidación). Consume eventos de `Communications`, `Quotes`, `Bookings`, `AfterSales` e `Integrations` (estado de vuelos); emite `SlaBreached`, `SlaAtRisk` y `SlaSettlementApproved` (este último hacia `Invoicing`). Usa `Workflow` para escalamientos y aprobaciones.
