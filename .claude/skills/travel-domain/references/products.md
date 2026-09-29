# Reglas por tipo de producto (`ProductType`)

Cada tipo tiene su DTO de búsqueda, de oferta y de reserva, y sus datos específicos en una tabla hija `booking_item_<tipo>_details` (no columnas nulas masivas en `booking_items`).

## Aéreo (`flight`)
- Fuentes: GDS (Amadeus, Sabre, Travelport), NDC directo de aerolíneas o agregadores NDC, consolidadores, low-cost vía API propia.
- Búsqueda: origen/destino IATA (aeropuerto o ciudad), fechas, ida / ida-vuelta / multidestino, pasajeros por tipo (ADT, CHD, INF), cabina, flexibilidad ±3 días, directo.
- Oferta: segmentos, escalas y duración, aerolínea operadora vs. comercializadora, **familia tarifaria** (brand) con equipaje, cambios, reembolso y selección de silla, desglose de tarifa base + impuestos (YQ/YR, tasas).
- Reserva: nombres EXACTOS como en el documento, fecha de nacimiento, género, documento (APIS/Secure Flight cuando aplica), contacto. Crea PNR con TTL → `ticketing_deadline_at`.
- Emisión: número de tiquete por pasajero; servicios auxiliares como EMD. Anulación (void) solo el mismo día ⚙ sin costo; después es reembolso según la tarifa.
- Post-venta: cambios de fecha (diferencia tarifaria + penalidad + fee), reembolsos, tiquetes no usados como crédito, cambios involuntarios (schedule change) que llegan por cola del GDS/webhook y **deben notificarse** al pasajero.
- Infante (< 2 años) viaja en brazos de un adulto: máximo 1 por adulto.

## Hotel / alojamiento (`hotel`)
- Fuentes: bancos de camas, conectividad directa/channel managers, contratos propios (tarifas y cupos cargados en `Catalog`).
- Búsqueda: destino (ciudad, zona, punto de interés o coordenadas + radio), check-in/out, habitaciones con adultos y edades de niños, nacionalidad del huésped (afecta precio en algunos proveedores).
- Deduplicar el mismo hotel de varios proveedores vía `HotelMapping`; mostrar la mejor tarifa y, opcionalmente, alternativas.
- Tarifa: tipo de habitación, régimen (RO/BB/HB/FB/AI), reembolsable o no, política de cancelación con fechas, impuestos incluidos vs. pagaderos en destino (city tax, resort fee) — estos últimos se muestran aparte y claramente.
- Flujo: `search → check rate (prebook) → book`. La tarifa puede cambiar entre búsqueda y prebook.
- Rooming list y solicitudes especiales (no garantizadas). Check-in/out en hora local del hotel.

## Alquiler de autos (`car_rental`)
- Fuentes: agregadores (brokers) o rentadoras directas.
- Búsqueda: lugar/fecha/hora de recogida y devolución (puede diferir: cargo de devolución en otra oficina), edad del conductor (recargo joven/senior).
- Oferta: categoría ACRISS/SIPP (ej. `ECMR`), transmisión, kilometraje, política de combustible, coberturas (CDW, TP, SLI), depósito/franquicia, requisitos (licencia internacional, tarjeta de crédito del titular).
- Días de alquiler por bloques de 24 h desde la hora de recogida (con tolerancia ⚙ del proveedor).

## Tours, actividades y pasadías (`tour`, `activity`, `day_pass`)
- Fuentes: producto propio (`Catalog`) y marketplaces/APIs de actividades.
- Modelo: `Product` → `ProductOption` (ej. "con almuerzo", "privado") → `Departure` (fecha/hora con capacidad) o disponibilidad libre por día.
- Precios por tipo de pasajero y edad (rangos configurables por producto), por grupo o por vehículo (privados), con temporadas y días de la semana.
- Capacidad: mínimo de pasajeros para operar (si no se alcanza a X horas ⚙, alerta para reprogramar/cancelar) y máximo por salida; control de sobreventa con bloqueo pesimista.
- Datos operativos: punto de encuentro o recogida en hotel (con hora por zona), idiomas del guía, duración, qué incluye/no incluye, qué llevar, restricciones (edad, salud, peso), accesibilidad, política de clima.
- **Pasadía:** uso de instalaciones en un día, con horario, qué incluye (almuerzo, bebidas, piscina), restricciones de cupo diario y a veces ingreso con documento.
- Voucher con QR para check-in en el punto de encuentro.

## Traslados (`transfer`)
- Privado o compartido, por vehículo o por pasajero; tipo de vehículo y capacidad (pasajeros + maletas).
- Llegada al aeropuerto: requiere número y hora de vuelo; se reprograma si el vuelo cambia (integra estado de vuelos).
- Tiempo de recogida para salida calculado desde la hora de vuelo − anticipación ⚙ − tiempo de trayecto.

## Seguro / asistencia al viajero (`insurance`)
- Obligatorio en ciertos destinos (ej. espacio Schengen, algunos países de la región) → el sistema **sugiere o exige** según destino ⚙.
- Plan por días y edad; cobertura médica mínima; emisión con datos del pasajero; certificado PDF.

## Paquete (`package`)
- Estático: armado por la agencia con precio cerrado por persona y acomodación.
- Dinámico: combinación en tiempo real (vuelo + hotel + otros) con precio agregado; puede ocultar el desglose ⚙ (tarifas empaquetadas).
- El paquete es un contenedor comercial; cada componente es un `BookingItem` con su proveedor.

## Otros (extensibles)
Cruceros, trenes, buses, eventos/entradas, visas y trámites, MICE, renta de equipos, servicios manuales genéricos (`other`) con proveedor y confirmación manual.
