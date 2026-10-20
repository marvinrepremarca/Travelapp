# Roles, permisos y seguridad

## 1. Roles

| Rol | Para quién | Alcance por defecto | 2FA |
|---|---|---|---|
| **Gerente de la agencia** | Dueño o gerente general | Toda la agencia | Obligatorio |
| **Administrador del sistema** | Persona de tecnología (soporte de la plataforma) | Toda la agencia | Obligatorio |
| **Director de sucursal** | Responsable de una sucursal | Su sucursal | — |
| **Asesor** | Vendedor | Lo suyo (configurable) | — |
| **Operaciones** | Coordinación en destino | Toda la agencia | — |
| **Gerente de producto** | Proveedores, precios y catálogo | Toda la agencia | — |
| **Finanzas** | Cobros, pagos, facturación y contabilidad | Toda la agencia | Obligatorio |

Los roles con 2FA obligatorio se definen en `TRAVEL_TWO_FACTOR_REQUIRED_ROLES`.

**Alcance** = qué registros ve el usuario: *propio* (lo que tiene a su cargo), *sucursal* o *todo*. Un registro fuera del alcance responde "no encontrado".

### Gerente vs. Administrador del sistema
- El **Gerente** es el dueño del negocio: tiene todos los permisos y es el **único** que administra Roles y permisos.
- El **Administrador del sistema** da soporte técnico dentro de la aplicación: usuarios, auditoría e integraciones. Por defecto **no** ve áreas del negocio (ventas, clientes, finanzas).
- La instalación y configuración del servidor (`.env`, migraciones, capacidades) ocurre fuera de la aplicación y no depende de ningún rol.

## 2. Administrar permisos (Gerente)

**Configurar la agencia → Roles y permisos** (`/identity/roles`): lista los roles; cada uno se edita en su propia página.

- Los cambios aplican de inmediato y quedan auditados.
- "Restaurar valores por defecto" vuelve a los permisos de fábrica del rol.
- El rol Gerente no se edita (siempre tiene todo) y "Administrar roles y permisos" está reservado a él.

## 3. Permisos

**Acceso a áreas** (sin ellos la pantalla no aparece en el menú ni abre): proveedores, catálogo, tasas de cambio y simulador, clientes, seguimiento de leads, conversaciones de WhatsApp, búsqueda de vuelos y hoteles, cotizaciones, expedientes, cobros, caja, tablero del asesor, tareas y aprobaciones.

**Administración y acciones**: administrar la agencia, sucursales, ver y administrar usuarios, administrar roles, ver auditoría, administrar integraciones, ver las capacidades del sistema, acceder a finanzas, ver márgenes, reasignar clientes, administrar proveedores y sus contactos y cuentas, ver la contraseña temporal de usuarios nuevos, administrar reglas de precio y catálogo, gestionar la operación, gestionar el cumplimiento, ver datos sensibles de pasajeros y exportar datos personales.

**Aprobaciones**: aprobar descuentos, reembolsos, anulaciones de factura, cambios de precio confirmados y exportaciones de datos personales.

> Un usuario solo puede asignar un rol cuyos permisos ya tenga (protección contra escalada de privilegios).

## 4. Usuarios

**Configurar la agencia → Usuarios** (`/identity/users`):

- **Contraseña temporal** (marcada por defecto): el sistema genera una contraseña de 16 caracteres que se muestra **una vez** para entregarla en persona. Se guarda cifrada; quien tenga el permiso puede volver a consultarla (queda auditado). El usuario debe cambiarla al primer ingreso.
- **Invitación por correo**: desmarca la casilla; el usuario recibe un enlace para definir su contraseña.
- Desactivar un usuario cierra sus sesiones y bloquea su ingreso; no se borra.

## 5. Página de inicio según el rol

- Con el permiso **"Ver las capacidades del sistema"** (Administrador del sistema y Gerente): estado de las capacidades.
- Los demás: su resumen personal (cuenta, sucursal, alcance, estado de 2FA) y accesos directos a lo que su rol puede hacer.

## 6. Seguridad

- **2FA** con aplicación autenticadora (Google Authenticator, Authy…) en **Seguridad**.
- **Auditoría** (`/audit`): toda mutación relevante (precios, estados, pagos, datos de pasajeros, permisos) queda registrada con quién, cuándo y qué cambió.
- Datos personales sensibles (documentos, fechas de nacimiento) cifrados en la base de datos y enmascarados en pantalla.
- Pagos con tarjeta solo por links/tokens de la pasarela: el sistema nunca guarda datos de tarjeta.
