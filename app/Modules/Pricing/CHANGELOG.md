# Changelog — Pricing

## Fase 2.1

### Agregado
- **Tasas de cambio** con fecha y fuente: TRM oficial automática (datos.gov.co, 06:00/08:00/10:00 hora de la agencia, `pricing:fetch-official-rate`) y carga manual de finanzas. La manual del día prevalece; el spread de la agencia es configurable.
- **Reglas de markup** por criterios (tipo de producto, proveedor, país de destino, canal): gana la más específica, luego la de mayor prioridad. Porcentaje o monto fijo por pasajero, noche o reserva, con margen mínimo y vigencia.
- **Fees de servicio** por reserva o por pasajero, opcionalmente por tipo de producto y canal.
- **Impuestos** con vigencia y exenciones por tipo de producto; el IVA (19 % de referencia) se calcula solo sobre el ingreso de la agencia (markup + fees).
- **Contrato `PriceCalculator`**: desglose congelable (neto, markup, fees, impuestos, tasa usada); el margen siempre es derivado.
- Pantallas *Reglas de precio* (permiso `pricing.manage`) y *Simulador de precio*; el margen se oculta a asesores salvo que la agencia lo permita.
