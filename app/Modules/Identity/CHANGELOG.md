# Changelog — Identity

## Fase 1.2

### Agregado
- **Usuarios internos:** listado con búsqueda, filtro por rol y paginación, filtrado por el alcance de quien consulta (un director de sucursal solo ve su sucursal; fuera de alcance responde 404).
- **Invitaciones:** el administrador crea el usuario y este recibe un correo para definir su contraseña; nadie más la conoce. Se puede reenviar.
- **Edición:** nombre, correo, rol, sucursal y alcance (el rol propone su alcance por defecto).
- **Activar / desactivar:** al desactivar se cierran todas las sesiones y el usuario no puede iniciar sesión (mismo mensaje que credenciales inválidas).
- **Anti-escalada de privilegios:** solo se pueden asignar roles cuyos permisos ya tenga quien asigna; nadie cambia su propio rol ni se desactiva a sí mismo; los alcances propio y sucursal exigen sucursal.
- **Seguridad de la cuenta:** pantalla con activación de 2FA (QR, confirmación, códigos de recuperación) y cambio de contraseña, protegida con confirmación de contraseña.
- **2FA obligatorio** para los roles configurados en `travel.security.two_factor_required_roles`: hasta activarlo, el usuario solo puede usar la pantalla de seguridad y no puede desactivarlo.
- Auditoría de cambios en usuarios (`log_name = identity`), sin contraseñas ni secretos.

## Ajuste (ambiente de pruebas)

### Cambiado
- Los roles con 2FA obligatorio se configuran con `TRAVEL_TWO_FACTOR_REQUIRED_ROLES` (por defecto `system_admin,agency_owner,finance`). Vacío desactiva la exigencia: **solo para el ambiente local de pruebas**, nunca en producción.

## 2026-10-13
- Roles y permisos (identity.roles): el Gerente de la agencia administra los permisos de los demás roles; su rol no se edita y administrar roles queda reservado a él (se retira del Administrador del sistema). Cambios auditados; el seeder ya no sobrescribe la configuración.
