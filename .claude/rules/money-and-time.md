# Reglas: dinero, monedas y tiempo (siempre activas)

Detalle en la skill `pricing-engine`.

<dinero>
- Un importe siempre viaja con su moneda: `Money`. Prohibido sumar importes de monedas distintas sin conversión explícita.
- Toda conversión registra `exchange_rate`, `rate_source` y `rate_date`. La tasa con la que se cotizó queda congelada en la reserva.
- Se guardan por separado **neto proveedor**, **markup/fee**, **comisión**, **impuestos** y **precio de venta**. El margen es derivado, nunca un dato ingresado a mano.
- Redondeo solo al presentar y al facturar, con `RoundingMode::HALF_UP` salvo regla fiscal distinta, y respetando los decimales de la moneda (COP 0 en facturación si así lo define la agencia, USD 2, JPY 0).
- El prorrateo (repartir un total entre pasajeros o cuotas) usa `Money::allocate()` para que no se pierdan centavos.
</dinero>

<tiempo>
- La BD guarda instantes en UTC. La UI muestra en la zona de la agencia, salvo fechas de servicio.
- Fechas de servicio (vuelo, check-in, tour) se muestran en **hora local del destino/origen**, con la zona indicada cuando puede confundir.
- Plazos (fecha límite de emisión, de pago, de cancelación sin penalidad) son instantes UTC calculados desde la zona del proveedor, y se muestran con cuenta regresiva.
- Noches de hotel = diferencia de fechas, no de horas. Edades de pasajeros se calculan a la **fecha del servicio**, no a hoy.
</tiempo>
