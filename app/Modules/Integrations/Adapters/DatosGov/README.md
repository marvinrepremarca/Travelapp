# Adaptador: TRM — Datos Abiertos Colombia

- **Qué entrega:** Tasa Representativa del Mercado (USD → COP) certificada por la Superintendencia Financiera.
- **API:** Socrata SODA, conjunto `32sa-8pi3` (`https://www.datos.gov.co/resource/32sa-8pi3.json`). Pública, sin credenciales.
- **Consulta:** `$where=vigenciadesde <= 'AAAA-MM-DDT00:00:00.000' AND vigenciahasta >= '…'` → una fila con `valor` (texto decimal), `unidad` (`COP`), `vigenciadesde`, `vigenciahasta`.
- **Particularidades:** la TRM de viernes cubre sábado, domingo y festivos (`vigenciahasta` > `vigenciadesde`); el adaptador devuelve el rango y la tasa se guarda para cada día.
- **Errores:** timeout, 5xx, respuesta vacía o malformada → `ExchangeRateUnavailable`; finanzas puede registrar la tasa a mano.
- **Configuración:** `config/suppliers.php` → `providers.datos_gov_trm` (timeouts, reintentos) y `allowed_hosts`.
- **Fixtures:** `tests/Fixtures/Suppliers/DatosGov/` (respuestas reales).
- **Verificado:** 2026-10-01.
