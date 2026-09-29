# Mapa de módulos (`app/Modules/*`)

El sistema pertenece a **una sola agencia** (con sus sucursales) y cubre de forma modular **toda** su administración y su gestión de viajes. Construye por fases: un módulo no se crea hasta que la fase lo necesite (YAGNI), pero sus límites ya están definidos aquí. Cada módulo se puede activar o desactivar por configuración sin afectar a los demás.

## A. Núcleo (transversal)

| Módulo | Responsabilidad | Expone (Contracts / Events) | Fase |
|---|---|---|---|
| `Shared` | Value objects (`Money`, `DateRange`, `PassengerMix`, `Address`), `BusinessRuleException`, enums transversales (`Currency`, `ProductType`, `SalesChannel`, `Tone`), trait `HasVisibilityScope` | — | 1 |
| `Organization` | Datos de la agencia (razón social, NIT, RNT, marca), sucursales/puntos de venta, configuración ⚙, calendarios y festivos, módulos activos | `AppSettings`, `BranchDirectory` | 1 |
| `Identity` | Usuarios internos, roles, permisos, alcance (own/branch/all), 2FA; usuarios de portal (viajero, empresa cliente, agencia aliada) | `UserRegistered` | 1 |
| `Audit` | Bitácora de actividad y de accesos a datos sensibles | — | 1 |
| `Workflow` | Tareas, agenda, recordatorios, asignaciones y aprobaciones genéricas (descuentos, reembolsos, pagos grandes) reutilizadas por los demás módulos | `TaskScheduler`, `ApprovalRequested`, `ApprovalResolved` | 1 |

## B. Gestión viajera (comercial y operación del viaje)

| Módulo | Responsabilidad | Expone | Fase |
|---|---|---|---|
| `Crm` | Clientes (personas/empresas), pasajeros, documentos de viaje, preferencias, programas de lealtad, leads, oportunidades, embudo, interacciones | `CustomerDirectory`, `LeadWon` | 1 |
| `Suppliers` | Proveedores, contratos, condiciones de pago, comisiones pactadas, contactos, evaluación de desempeño, RNT de proveedores | `SupplierDirectory` | 1 |
| `Catalog` | Producto propio: tours, pasadías, actividades, traslados, paquetes, alojamientos contratados; temporadas, tarifas, cupos, capacidad, contenido multidioma | `ProductAvailability`, `ProductRates` | 2 |
| `Integrations` | Adaptadores de plataformas externas: **Amadeus** (vuelos, hoteles, autos, traslados, tours y actividades) como primera; después otros GDS/NDC, bancos de camas, rent-a-car, actividades, seguros, estado de vuelos | Puertos por tipo de producto | 2 |
| `Search` | Búsqueda unificada (Amadeus + producto propio + otros proveedores), normalización, deduplicación, caché, filtros | `SearchService` | 2 |
| `Pricing` | Markups, fees, comisiones, descuentos, promociones, impuestos, tasas de cambio, redondeo | `PriceCalculator`, `ExchangeRates` | 2 |
| `Quotes` | Cotizaciones versionadas multi-opción, constructor de itinerarios día a día, plantillas, envío y aceptación en línea | `QuoteAccepted` | 2 |
| `Bookings` | Expedientes, ítems, pasajeros por ítem, estados, plazos, cambios y cancelaciones, rooming lists, sagas de reserva | `BookingConfirmed`, `BookingCancelled`, `BookingItemChanged`, `DeadlineApproaching` | 2 |
| `Documents` | Vouchers, itinerarios, contratos de servicios, cotizaciones PDF/web con la marca de la agencia, archivos de pasajeros | `DocumentGenerator` | 2 |
| `Operations` | Operación de producto propio y receptivo: salidas, manifiestos, guías, vehículos, conductores, puntos de encuentro, check-in, incidencias en destino | `DepartureClosed` | 3 |
| `Corporate` | Empresas cliente, políticas de viaje, aprobaciones, centros de costo, duty of care, tiquetes no usados, contratos de servicio con SLA (relojes, turnos 24/7, liquidación de penalidades) | `TripApproved`, `PolicyViolationDetected`, `SlaAtRisk`, `SlaBreached`, `SlaSettlementApproved` | 4 |
| `Groups` | Grupos y MICE: bloqueos, inscripciones, rooming lists, pagos por participante | — | 4 |
| `Portal` | Tienda online B2C, portal del viajero ("Mi viaje"), portal B2B para agencias aliadas, portal corporativo | — | 3 |
| `Communications` | Envío multicanal (correo, WhatsApp, SMS, push), bandeja unificada por expediente, plantillas, preferencias de contacto | `MessageSent` | 3 |
| `AfterSales` | PQRS y reclamos, cambios post-venta, encuestas NPS/CSAT, reseñas | — | 4 |
| `Marketing` | Campañas, códigos promocionales, fidelización (puntos), segmentación, landing pages | — | 5 |

## C. Administración (gestión interna de la agencia)

| Módulo | Responsabilidad | Expone | Fase |
|---|---|---|---|
| `Payments` | Cobros al cliente (pasarelas, links de pago, transferencias, efectivo), planes de pago/abonos, pagos divididos, reembolsos, saldos a favor, tarjetas virtuales a proveedores | `PaymentReceived`, `RefundIssued` | 3 |
| `Finance` | Cuentas por cobrar y por pagar, liquidación a proveedores, BSP, caja por sucursal, bancos y conciliación, libro de movimientos, rentabilidad por expediente, cierre de período, exportación/integración contable | `SupplierInvoiceRegistered`, `PeriodClosed` | 3 |
| `Invoicing` | Facturas internas, notas crédito/débito, mandato vs. ingreso propio, numeración y resolución de facturación. **Sin integración electrónica por ahora**, pero preparado: puerto `EInvoicingProvider` con adaptador `NullEInvoicingProvider` (ADR-0004) | `InvoiceIssued`, `InvoiceReadyForElectronicSubmission` | 3 |
| `Expenses` | Gastos administrativos y compras de la agencia (arriendo, servicios, publicidad, suscripciones), caja menor, presupuestos por sucursal/área, aprobación de gastos | `ExpenseApproved` | 4 |
| `Staff` | Colaboradores (asesores, operaciones, guías propios), sucursal y cargo, metas de venta, esquemas y liquidación de comisiones de vendedores, capacitaciones obligatorias (p. ej. prevención ESCNNA), vencimiento de tarjetas profesionales de guías | `CommissionSettled` | 4 |
| `Compliance` | Cumplimiento legal: RNT y su renovación, pólizas y garantías, contratos, políticas de datos personales y consentimientos, solicitudes de titulares (Ley 1581), reportes fiscales/parafiscales (FONTUR), calendario de obligaciones | `ObligationDue` | 3 |
| `Reports` | KPIs, dashboards por rol y reportes; lectura (solo SELECT) transversal, nunca escribe (ADR-0001) | — | 3 |

> Nómina, contabilidad completa (plan de cuentas, estados financieros oficiales) e inventario de activos **no** se construyen: se integran con el ERP/software contable y de nómina que use la agencia mediante exportaciones o API (ADR cuando se elija).

## Dependencias permitidas (dirección de las flechas)

```
Shared, Organization, Identity, Audit, Workflow  ←  todos
Crm, Suppliers         ←  Quotes, Bookings, Corporate, Finance, Staff
Catalog, Integrations  ←  Search  ←  Quotes, Bookings, Portal
Pricing                ←  Search, Quotes, Bookings, Portal
Bookings  ── events ──►  Payments, Finance, Invoicing, Documents, Operations, Communications, AfterSales, Staff (comisiones)
Payments  ── events ──►  Bookings, Finance, Invoicing
Expenses, Invoicing ── events ──►  Finance
Compliance ── events ──► Workflow, Communications
```

Ciclos prohibidos. Si un módulo "de abajo" necesita reaccionar a uno "de arriba", usa eventos.

## Roadmap sugerido

1. **Fase 1 – Base:** Organization, Identity, Audit, Workflow, Crm, Suppliers.
2. **Fase 2 – Vender:** Catalog, Pricing, Quotes, Bookings (proveedores manuales primero), Documents, Integrations + Search con **Amadeus vuelos y hoteles**.
3. **Fase 3 – Cobrar, facturar y operar:** Payments, Finance, Invoicing, Compliance, Operations, Portal (viajero y B2C), Communications, Reports.
4. **Fase 4 – Ampliar:** Amadeus autos, traslados, tours y actividades; otros proveedores; Corporate, Groups, AfterSales, Expenses, Staff, portal B2B, API.
5. **Fase 5 – Diferenciar:** Marketing, fidelización, asistente IA, funciones de `value-added.md`.
