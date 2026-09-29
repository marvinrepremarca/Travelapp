# Glosario (negocio → código)

| Término de negocio (ES) | Código | Nota |
|---|---|---|
| Agencia | `Agency` (registro único) | La empresa dueña del sistema: razón social, NIT, RNT, marca |
| Sucursal / punto de venta | `Branch` | Caja, series de facturación y metas por sucursal |
| Asesor / agente de viajes | `User` con rol `travel_agent` | Vendedor interno |
| Subagencia / agencia afiliada | `PartnerAgency` | Compra en B2B con tarifa neta o comisión |
| Cliente | `Customer` (`type`: person/company) | Quien compra/paga |
| Cuenta corporativa | `CorporateAccount` | Empresa cliente con política de viajes |
| Contrato de servicio / SLA | `ServiceAgreement` | Contrato versionado con indicadores, calendario de atención y penalidades |
| Indicador de SLA | `SlaMetric` (enum) | Ej. `first_response_time`, `emergency_response_time` |
| Prioridad de SLA | `SlaPriority` (enum) | `critical`, `high`, `normal`, `low` |
| Reloj de SLA | `SlaTimer` | `running`, `paused`, `met`, `breached`; instantes UTC |
| Turno de guardia | `OnCallShift` | Cobertura 24/7 por franja y zona horaria |
| Liquidación de SLA | `SlaSettlement` | Penalidad/bonificación del período; termina en nota crédito |
| Pasajero / viajero | `Traveler` | Quien viaja. Tipo por edad: `adult`, `child`, `infant` (`PassengerType`) |
| Titular / pasajero principal | `leadTraveler` | Contacto del servicio |
| Documento de viaje | `TravelDocument` | Pasaporte, cédula, visa; cifrado |
| Lead / prospecto | `Lead` | Solicitud aún no cotizada |
| Oportunidad | `Opportunity` | Embudo comercial |
| Cotización | `Quote` / `QuoteVersion` / `QuoteOption` | Versionada, multi-opción |
| Itinerario | `Itinerary` / `ItineraryDay` / `ItinerarySegment` | Día a día, narrativo y operativo |
| Expediente / file / reserva | `Booking` (`booking_reference`) | Contenedor comercial |
| Servicio / ítem | `BookingItem` (`product_type`) | Vuelo, hotel, auto, etc. |
| Localizador del proveedor / PNR | `supplier_reference` | En aéreo: record locator de 6 caracteres |
| Número de tiquete | `ticket_number` | 13 dígitos (3 de aerolínea + 10) |
| EMD | `Emd` | Documento para servicios aéreos adicionales (equipaje, silla) |
| Fecha límite de emisión | `ticketing_deadline_at` | TTL de la reserva aérea |
| Voucher / bono | `Voucher` | Documento que el proveedor acepta como prueba de pago |
| Rooming list | `RoomingList` | Distribución de pasajeros por habitación |
| Régimen / plan de alimentación | `BoardType` | RO (solo alojamiento), BB (desayuno), HB (media pensión), FB (pensión completa), AI (todo incluido) |
| Acomodación | `Occupancy` | Sencilla, doble, triple, cuádruple; adultos + edades de niños |
| Cupo / allotment | `Allotment` | Inventario bloqueado con fecha de liberación (`release_days`) |
| Fecha de liberación | `release_at` | Después de ella el cupo no vendido vuelve al proveedor |
| Temporada | `Season` | Alta, media, baja, especial; rangos de fechas |
| Tarifa | `Rate` / `RatePlan` | Precio por temporada, ocupación, tipo de pasajero |
| Tarifa neta | `net` | Lo que se paga al proveedor |
| Tarifa pública / rack | `gross` / `public_price` | Precio al público sugerido |
| Comisión | `Commission` | Del proveedor a la agencia, o de la agencia a vendedor/subagencia |
| Markup / margen / fee | `Markup`, `ServiceFee` | Recargo de la agencia |
| Fee de gestión / cargo por servicio | `ServiceFee` | Tarifa administrativa por emisión, cambio o cancelación |
| Tasas e impuestos | `Tax` (`TaxType`) | IVA, tasa aeroportuaria, YQ/YR (combustible), impuestos de destino |
| Política de cancelación | `CancellationPolicy` + `PenaltyRule` | Snapshot por ítem |
| No-show | `NoShow` | Pasajero no se presenta; suele cobrarse 100 % |
| Abono / anticipo | `Deposit` | Pago parcial |
| Plan de pagos | `PaymentSchedule` / `Installment` | Cuotas con vencimiento |
| Link de pago | `PaymentLink` | Pago en línea sin iniciar sesión |
| Saldo a favor / crédito | `CustomerCredit` | Resultado de reembolsos no monetarios |
| Liquidación a proveedor | `SupplierSettlement` | Pago de lo adeudado al proveedor |
| Factura del proveedor | `SupplierInvoice` | Cuenta por pagar |
| BSP / ARC | `BspReport` | Sistema de liquidación de IATA con aerolíneas (quincenal/semanal) |
| Tarjeta virtual (VCC) | `VirtualCard` | Pago a proveedores por tarjeta de un solo uso |
| Salida (de tour) | `Departure` | Fecha/hora concreta de un producto con capacidad |
| Pasadía / day pass | `ProductType::DayPass` | Uso de instalaciones (hotel, club, parque) por un día |
| Manifiesto | `Manifest` | Lista de pasajeros de una salida o traslado |
| Punto de encuentro | `MeetingPoint` | Lugar y hora de recogida |
| Guía / conductor | `Guide`, `Driver` | Recursos operativos |
| Traslado | `Transfer` | Aeropuerto↔hotel, privado o compartido |
| GDS | `Gds` | Amadeus, Sabre, Travelport |
| NDC | `Ndc` | Estándar IATA para distribución directa de aerolíneas |
| Banco de camas | `BedBank` | Mayorista hotelero (Hotelbeds, WebBeds, Expedia Rapid…) |
| Mapeo de hoteles | `HotelMapping` | Unificar el mismo hotel de distintos proveedores (GIATA u otro) |
| Política de viajes | `TravelPolicy` | Reglas corporativas de gasto y clase |
| Aprobador | `Approver` | Autoriza viajes fuera de o dentro de política |
| Centro de costo | `CostCenter` | Imputación contable corporativa |
| Duty of care | `DutyOfCare` | Deber de cuidado: ubicar y asistir al viajero corporativo |
| Tiquete no usado | `UnusedTicket` | Crédito aéreo reutilizable |
| PQRS / reclamo | `Claim` | Peticiones, quejas, reclamos, sugerencias |
| MICE | `ProductType::Mice` | Reuniones, incentivos, congresos y eventos |
