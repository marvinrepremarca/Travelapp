# Roles y permisos

Roles base (sembrados; el administrador puede crear roles propios combinando permisos). Permisos como enum `Permission` con formato `modulo.accion` (`bookings.cancel`, `finance.refunds.approve`).

| Rol | Alcance típico |
|---|---|
| `system_admin` | Soporte técnico: usuarios, configuración, integraciones, logs. Sin acceso a finanzas ni a documentos completos de pasajeros salvo permiso explícito |
| `agency_owner` | Todo en su agencia, incluida configuración, usuarios y finanzas |
| `branch_manager` | Su sucursal: ventas, caja, metas, aprobaciones de descuentos |
| `travel_agent` | Leads, cotizaciones, expedientes propios (o de su sucursal ⚙), cobros; no ve márgenes si la agencia lo oculta ⚙ |
| `operations` | Confirmaciones manuales, vouchers, manifiestos, guías, vehículos, incidencias |
| `product_manager` | Catálogo propio, temporadas, tarifas, cupos, contenido, markups |
| `finance` | Cuentas por cobrar/pagar, liquidaciones, BSP, facturación, reembolsos, caja, conciliación |
| `corporate_travel_manager` | (Portal corporativo) viajeros, políticas, reportes de su empresa |
| `approver` | (Portal corporativo) aprobar/rechazar viajes |
| `partner_agent` | (Portal B2B) buscar y reservar con tarifa neta, ver sus expedientes y comisiones |
| `traveler` | (Portal del viajero) sus viajes, documentos, pagos y mensajes |

Reglas:
- Acciones sensibles con permiso propio y, según monto ⚙, doble aprobación: aplicar descuento > X %, reembolsar, anular factura, cambiar precio confirmado, ver datos completos de documentos, exportar datos personales.
- Visibilidad por alcance: `own`, `branch`, `all` (scope de consulta según el rol).
