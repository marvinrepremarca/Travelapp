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
