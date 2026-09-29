---
name: pricing-engine
description: Motor de precios de viajes. Úsala al calcular o mostrar precios, markups, fees de servicio, comisiones (entrantes y salientes), descuentos, promociones, impuestos, conversiones de moneda y tasas de cambio, redondeos, prorrateo entre pasajeros o cuotas, tarifas por temporada/ocupación/edad, y penalidades de cancelación o cambio.
---

# Motor de precios

## Principios
- `Money` (brick/money) siempre; nunca `float`. Porcentajes como `BigDecimal` o puntos básicos.
- El precio es una **composición trazable**: cada componente queda guardado (`price_components`) para explicar el total al cliente, al asesor y a finanzas.
- Determinista y puro: `PriceCalculator` no consulta la BD; recibe reglas ya cargadas. Fácil de probar con datasets.
- El precio final del cliente se calcula **siempre en servidor** y se congela (snapshot) al reservar.

## Componentes de precio

| Componente | Código | Quién lo define |
|---|---|---|
| Neto proveedor | `supplier_net` | Proveedor / contrato |
| Precio público del proveedor | `supplier_gross` | Proveedor (tarifa comisionable) |
| Comisión del proveedor (entrante) | `supplier_commission` | Contrato |
| Markup | `markup` | Reglas de la agencia |
| Fee de servicio | `service_fee` | Reglas de la agencia (por pasajero, por expediente, por transacción) |
| Descuento / promoción | `discount` | Promociones, códigos, autorización manual |
| Impuestos incluidos | `tax_included` | Fiscal / proveedor |
| Impuestos pagaderos en destino | `tax_at_destination` | Proveedor (informativo, no se cobra) |
| Recargo por medio de pago | `payment_surcharge` | Agencia ⚙ |

## Pipeline de cálculo (patrón Pipeline)

```
BaseRate (neto o bruto, convertido a la moneda de venta)
 → ApplyMarkupRules         (la regla más específica gana)
 → ApplyServiceFees
 → ApplyPromotions          (acumulables o exclusivas ⚙)
 → ApplyChannelAdjustments  (B2C, B2B neto, corporativo con tarifa negociada)
 → ApplyTaxes               (reglas con vigencia y país)
 → Round                    (según moneda y regla de presentación)
 = PriceBreakdown           (componentes + total + margen)
```

## Reglas de markup / fee (`PricingRule`)
- Criterios combinables: agencia, canal, tipo de producto, proveedor, destino (país/ciudad), cadena/categoría de hotel, aerolínea, cabina, temporada/fechas de viaje, anticipación, cliente/segmento/cuenta corporativa, subagencia.
- Tipo: porcentaje, monto fijo por pasajero/noche/expediente, o precio objetivo.
- Prioridad explícita + especificidad; vigencia desde/hasta; mínimo y máximo de margen ⚙.
- **Nunca vender por debajo del neto** salvo autorización con permiso específico (queda auditada).

## Monedas
- Moneda de la agencia (contable), moneda de venta (lo que ve el cliente) y moneda del proveedor.
- `ExchangeRateProvider` (puerto): fuente oficial (TRM para COP) + tasas propias de la agencia con spread ⚙. Se guarda tasa, fuente y fecha.
- Al cotizar se congela la tasa hasta `quote.expires_at`; al reservar, en el ítem. La diferencia en cambio al pagar al proveedor se registra en `Finance`.

## Tarifas de producto propio
- `Rate` por `Season` × `ProductOption` × tipo de pasajero/rango de edad × ocupación (hotel: SGL/DBL/TPL, suplemento sencillo, niño con 2 adultos) × día de la semana.
- Reglas de niños: gratis hasta edad X ⚙, % del adulto, o tarifa fija; máximo de niños gratis por habitación.
- Estancias que cruzan temporadas se calculan **por noche** con la tarifa de cada noche.
- Precio por grupo/vehículo (privados) con escalones de pasajeros.

## Penalidades de cancelación y cambio
- `CancellationPolicy` snapshot = lista de `PenaltyRule` (`from` instante, `amount` fijo | % | noches, sobre neto o venta).
- `CancellationPenaltyCalculator::for(item, effectiveAt)` → penalidad del proveedor + fee de la agencia + reembolso neto al cliente. Siempre respetando lo efectivamente pagado.
- Los plazos se evalúan en la zona horaria del proveedor y se muestran en la del cliente.

## Tests
Datasets exhaustivos: especificidad de reglas, temporadas cruzadas, edades límite, redondeos por moneda (COP, USD, EUR, JPY), prorrateo con residuo, promociones acumulables/exclusivas, penalidades justo antes y justo después de cada corte.

## Checklist de cumplimiento
- [ ] Solo `Money`/`BigDecimal`; cero `float`.
- [ ] Componentes del precio guardados (`price_components`) y margen derivado.
- [ ] Reglas de markup/fee/promoción desde BD o configuración, con prioridad y vigencia; nada quemado.
- [ ] Tasa de cambio con fuente y fecha, congelada en cotización/reserva.
- [ ] Redondeo solo al presentar o facturar, según la moneda; prorrateo con `allocate()`.
- [ ] Nunca bajo el neto sin permiso auditado; precio recalculado en servidor.
- [ ] Penalidades evaluadas en la zona del proveedor con tests justo antes/después de cada corte.
- [ ] Datasets de tests para reglas, temporadas, edades, monedas y redondeos.
