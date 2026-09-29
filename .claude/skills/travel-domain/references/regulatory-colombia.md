# Marco regulatorio y fiscal (Colombia primero; extensible)

> Esta referencia orienta el diseño, no reemplaza asesoría legal. Todo valor, tarifa o porcentaje fiscal es ⚙ (configurable con vigencia desde/hasta), porque cambia por ley o por reforma tributaria. Antes de implementar una regla fiscal concreta, **confirma el valor vigente con el usuario**.

Diseña lo legal/fiscal detrás de una interfaz por país (`CountryCompliance`) para poder operar agencias de otros países sin tocar el dominio.

## Registro y operación
- **Registro Nacional de Turismo (RNT):** la agencia y los prestadores (hoteles, operadores, guías) deben tenerlo vigente. Guarda el RNT de la agencia (se imprime en vouchers, facturas y web) y el de los proveedores locales, con alertas de vencimiento de la renovación anual.
- **Ley 300 de 1996 (Ley General de Turismo) y sus reformas (p. ej. Ley 2068 de 2020)**, y normas sobre las responsabilidades de las agencias frente al usuario: información clara de condiciones, cancelaciones y reembolsos.
- **Estatuto del Consumidor (Ley 1480 de 2011):** información veraz del precio total, derecho de retracto en ventas a distancia cuando aplique ⚙, reversión del pago en compras electrónicas; términos y condiciones aceptados y versionados en el checkout.
- **Prevención de la explotación sexual comercial de niños, niñas y adolescentes (Ley 679 de 2001, Ley 1336 de 2009):** las agencias deben adoptar un código de conducta, advertir en sus documentos y capacitar. El sistema imprime la advertencia legal en vouchers/contratos ⚙ y marca los viajes con menores.
- **Guías de turismo:** tarjeta profesional vigente para los guías asignados en `Operations`.

## Datos personales
- **Ley 1581 de 2012 y Decreto 1377 de 2013:** autorización previa y expresa del titular, finalidades, política de tratamiento publicada, derechos ARCO (consultar, actualizar, rectificar, suprimir), registro de bases de datos ante la SIC cuando aplique. Datos de menores y de salud = sensibles.
- Viajeros de la UE → GDPR; transferencias internacionales a proveedores (aerolíneas, hoteles) deben estar en la política y en el consentimiento.

## Fiscal
- **Facturación electrónica DIAN** (Resolución de facturación vigente): factura, notas crédito/débito, documento soporte para compras a no obligados, eventos de la factura (acuse, aceptación). **Hoy no hay integración** (ADR-0004); el sistema queda listo para conectarse después con un **proveedor tecnológico** mediante el puerto `EInvoicingProvider`, sin tocar el dominio. Nunca se genera el XML UBL a mano en el dominio.
- **IVA:** tratamiento diferente según el servicio y el esquema (intermediación vs. venta propia), exenciones en servicios turísticos a no residentes, tiquetes aéreos internacionales, etc. Modela los impuestos como reglas con vigencia, nunca como constantes.
- **Contribución parafiscal para la promoción del turismo (FONTUR):** liquidación periódica sobre la base que defina la norma vigente; reporte en `Reports`.
- **Retenciones** (fuente, IVA, ICA) según el tipo de cliente/proveedor y el municipio.
- **Tasas y cargos de terceros** (tasa aeroportuaria, impuesto de timbre, tasas de destino) se muestran desglosados.
- **TRM** del Banco de la República como fuente por defecto para conversiones COP ⚙.

## Retención documental
Facturas, soportes contables y contratos se conservan el tiempo que exija la ley ⚙; los datos personales solo mientras exista finalidad. El borrado de datos personales anonimiza, no rompe la integridad contable.
