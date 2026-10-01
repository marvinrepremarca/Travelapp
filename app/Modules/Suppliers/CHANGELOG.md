# Changelog — Suppliers

## Fase 1.6

### Agregado
- **Proveedores** (catálogo de la agencia): datos legales, país, contacto, prepago o crédito con días y moneda de pago. Se desactivan, nunca se borran.
- **RNT:** obligatorio para prestadores turísticos colombianos; situación "habilitado / RNT por vencer / sin RNT / RNT vencido / inactivo". Sin RNT o con RNT vencido, el proveedor no es reservable.
- **Comisiones pactadas** por tipo de producto, sobre tarifa pública o neta, en puntos básicos y con vigencia sin solapes; se terminan, no se borran.
- **Cuentas bancarias** con número cifrado y enmascarado; solo finanzas las registra y las ve completas, con motivo y auditoría.
- **Contrato `SupplierDirectory`**: situación para reservar en una fecha y comisión vigente por tipo de producto.
- Permiso `suppliers.manage` para gerente, gestor de producto y finanzas; todos los usuarios internos consultan.
