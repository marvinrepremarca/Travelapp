---
name: travel-domain
description: Conocimiento del negocio de agencias de viajes y turismo (receptivo, emisivo, corporativo/TMC, operador de tours y pasadías). Úsala SIEMPRE que implementes o revises reglas de negocio: expedientes/reservas, cotizaciones e itinerarios, vuelos, hoteles, autos, tours, actividades, traslados, seguros, paquetes, cupos, temporadas, pagos y abonos, facturación, liquidación a proveedores, cancelaciones y reembolsos, viajes corporativos, portal del viajero, o cuando necesites el glosario negocio→código o el mapa de módulos.
---

# Dominio: agencia de viajes y operador turístico

El sistema gestiona los procesos internos de **una agencia de viajes** y sus sucursales. La agencia puede operar en uno o varios **modelos de negocio** a la vez, y el sistema debe soportarlos todos:

| Modelo | Qué hace | Implicación en el sistema |
|---|---|---|
| Emisiva / minorista (OTA o agencia física) | Vende viajes al exterior o nacionales a clientes finales | Buscador multi-proveedor, cotizador, checkout, portal del viajero |
| Receptiva / DMC | Opera servicios en destino para viajeros que llegan | Producto propio, operación (guías, vehículos, manifiestos), venta B2B a otras agencias |
| Mayorista / consolidador | Arma paquetes y vende a otras agencias | Tarifas netas, cupos (allotments), portal B2B, comisiones a subagencias |
| TMC (viajes corporativos) | Gestiona viajes de empleados de empresas | Políticas de viaje, aprobaciones, centros de costo, duty of care, reporting de gasto |
| Operador de tours / pasadías / actividades | Diseña y opera experiencias propias | Salidas programadas, capacidad, puntos de encuentro, recursos, check-in |

## Conceptos centrales (lee `references/glossary.md` para la lista completa)

- **Expediente** (`Booking`, a veces llamado *file* o *record*): contenedor comercial de un viaje para un cliente. Agrupa **ítems** (`BookingItem`) de distintos tipos de producto y proveedores, pasajeros, pagos, documentos y facturas. El número visible es `booking_reference` (ej. `TA-2026-000123`).
- **Ítem de reserva** (`BookingItem`): un servicio concreto (vuelo, noche(s) de hotel, auto, tour…) con su proveedor, localizador del proveedor (`supplier_reference`, PNR en aéreo), estado propio, precio neto/venta y política de cancelación **congelada** (snapshot) al reservar.
- **Cotización** (`Quote`): propuesta versionada con una o varias **opciones** y un **itinerario** día a día. Se convierte en expediente al aceptarse. Tiene vigencia; los precios de proveedores externos no están garantizados hasta reservar.
- **Pasajero** (`Traveler`) vs **cliente** (`Customer`): quien viaja no siempre es quien paga. Un cliente puede ser persona o empresa (`CorporateAccount`).
- **Proveedor** (`Supplier`): Amadeus (plataforma principal de reservas de vuelos, hoteles, autos, traslados y actividades), aerolínea, hotel, banco de camas, rentadora, operador local, aseguradora. Puede estar conectado por API (`connection_type = api`) o ser manual (`manual`: la agencia confirma por correo/teléfono y registra el localizador).
- **Producto propio** (`Product`): tours, pasadías, actividades, traslados o paquetes que la agencia diseña, con temporadas, tarifas y cupos/capacidad.

## Reglas de negocio transversales

1. **El estado del expediente se deriva del estado de sus ítems** (ver `references/booking-lifecycle.md`). Nunca se cambia a mano sin una Action que lo justifique.
2. **Snapshot al reservar:** precio, moneda, tasa de cambio, política de cancelación, condiciones tarifarias, impuestos y contenido descriptivo esencial se copian al ítem. Cambios posteriores del catálogo o del proveedor no alteran reservas existentes.
3. **Precio de venta = neto proveedor + markup/fee − descuentos + impuestos**, o bien **precio público − comisión** cuando el proveedor da tarifa comisionable. Ambos modelos coexisten (ver skill `pricing-engine`).
4. **Plazos críticos** (fecha límite de emisión aérea, de pago al proveedor, de cancelación gratuita, de pago del cliente, de entrega de rooming list) generan tareas y alertas automáticas antes del vencimiento. Un plazo vencido sin acción es el mayor riesgo operativo y financiero de una agencia.
5. **Pagos del cliente y pagos a proveedores son flujos separados.** La agencia puede cobrar abonos y el saldo en cuotas; debe pagar al proveedor según su contrato (prepago, crédito, BSP). El sistema muestra siempre saldo del cliente y saldo con cada proveedor por expediente.
6. **Cancelaciones y cambios** calculan penalidad del proveedor + penalidad/fee de la agencia según la política snapshot y la fecha efectiva; el reembolso nunca supera lo pagado y puede ser dinero, saldo a favor o voucher de crédito.
7. **Datos de pasajeros** deben coincidir con el documento de viaje (nombre como en el pasaporte, fecha de nacimiento, nacionalidad, documento y vencimiento). Se valida vigencia ≥ 6 meses para internacionales (configurable por destino) y la edad a la fecha del servicio (adulto/niño/infante).
8. **Menores de edad:** marca el viaje con menores, exige datos del acompañante y muestra advertencias de permisos de salida del país; la agencia debe cumplir la política de prevención de explotación sexual de menores (ver `references/regulatory-colombia.md`).
9. **Todo documento entregado al cliente** (cotización, voucher, itinerario, factura, contrato de servicios) se genera desde datos del sistema, con la marca de la agencia, y queda versionado.
10. **Multi-canal:** una reserva puede originarse en backoffice (asesor), portal B2C, portal B2B (subagencia), API o importación. El canal (`sales_channel`) se registra siempre y condiciona precios y comisiones.

## Referencias (carga la que aplique)

- `references/modules.md` — mapa de módulos, responsabilidades y dependencias.
- `references/glossary.md` — glosario español ↔ código y términos de la industria.
- `references/roles.md` — roles, alcances de visibilidad y acciones con doble aprobación.
- `references/booking-lifecycle.md` — estados de expediente, ítem, cotización y pago; transiciones.
- `references/products.md` — reglas por tipo de producto (aéreo, hotel, auto, tour, pasadía, actividad, traslado, seguro, paquete, crucero).
- `references/finance.md` — cobros, abonos, pagos a proveedores, BSP, comisiones, conciliación, facturación.
- `references/corporate.md` — viajes de negocios: políticas, aprobaciones, duty of care, SLA contractuales y su medición.
- `references/regulatory-colombia.md` — marco legal y fiscal (Colombia primero; extensible por país).
- `references/value-added.md` — diferenciadores de producto que el sistema debe ofrecer.

## Parámetros de negocio ⚙

Todo valor marcado ⚙ en las referencias es **configurable** desde la administración (tabla `settings` con defaults en `config/travel.php`). Nunca lo quemes en código.

## Cuando falte una regla

No la inventes. Pregunta al usuario, propone la opción más común en la industria como recomendación y, cuando se decida, agrégala a la referencia correspondiente.

## Checklist de cumplimiento
- [ ] Las reglas implementadas están en una referencia de esta skill (o se agregaron tras confirmarlas con el usuario).
- [ ] Términos y nombres de código según `references/glossary.md`.
- [ ] Transiciones de estado según `references/booking-lifecycle.md`, con evento, auditoría, actor y motivo.
- [ ] Snapshot de precio, moneda, tasa y política de cancelación al reservar.
- [ ] Plazos críticos generan alertas/tareas; edades calculadas a la fecha del servicio.
- [ ] Pagos del cliente y pagos a proveedores tratados como flujos separados.
- [ ] Todo parámetro ⚙ viene de configuración, nunca quemado.
- [ ] Módulo y dependencias respetan `references/modules.md`; permisos según `references/roles.md`.
