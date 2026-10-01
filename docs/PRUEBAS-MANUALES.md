# Pruebas manuales en local

## Levantar la aplicación

```bash
composer install && npm install && npm run build
php artisan migrate:fresh
php artisan db:seed --class=DemoSeeder
php artisan storage:link
php artisan serve
```

Abrir http://127.0.0.1:8000/travelapp (la carpeta se define con `APP_PATH_PREFIX`; vacía = raíz del dominio). En `.env` local: `QUEUE_CONNECTION=sync` (los correos salen al instante) y `MAIL_MAILER=log` (los correos se escriben en `storage/logs/laravel.log`).

## Usuarios de demostración

Contraseña de todos: `ViajesDemo2026` (variable `TRAVEL_DEMO_PASSWORD`).

| Correo | Rol | Sucursal | Nota |
|---|---|---|---|
| gerente@viajesdemo.test | Gerente de la agencia | Bogotá | Exige 2FA: la primera vez te lleva a configurarlo |
| admin@viajesdemo.test | Administrador del sistema | Bogotá | Exige 2FA |
| finanzas@viajesdemo.test | Finanzas | Bogotá | Exige 2FA |
| director.bogota@viajesdemo.test | Director de sucursal | Bogotá | Aprueba descuentos de Bogotá |
| director.medellin@viajesdemo.test | Director de sucursal | Medellín | Aprueba descuentos de Medellín |
| asesor.bogota@viajesdemo.test | Asesor | Bogotá | Solo ve lo suyo |
| asesor.medellin@viajesdemo.test | Asesor | Medellín | Solo ve lo suyo |
| operaciones@viajesdemo.test | Operaciones | Bogotá | |

Para el 2FA usa una app de autenticación (Google Authenticator, Microsoft Authenticator, Authy).

## Guion por fase

### Fase 0 — Base
- [ ] `/travelapp/health` responde `{"status":"up",...}`.
- [ ] `/travelapp/design-system` (con sesión) muestra todos los componentes; navega con Tab y verifica el foco visible.

### Fase 1.1 — Organization (usuario: gerente)
- [ ] **Datos de la agencia:** cambia el color principal a `#fde047` → error de contraste; a `#0f766e` → se guarda y el menú cambia de color. Pon un dígito de verificación incorrecto → error. Sube un logo PNG.
- [ ] Aparece la alerta de RNT por vencer (el demo vence en 30 días).
- [ ] **Sucursales:** crea una, busca, filtra por estado, desactívala y reactívala. Asigna como responsable a un asesor → no aparece en la lista (solo directores).
- [ ] **Festivos:** año 2026 muestra 18 festivos. Agrega el 24 de diciembre; intenta "trabajar" el 13 de enero (no es festivo) → error.
- [ ] **Parámetros:** cambia la vigencia de cotizaciones a 48 y guarda.

### Fase 1.2 — Identity
- [ ] Entra como **gerente** por primera vez → te obliga a activar 2FA en *Seguridad*. Escanea el QR, confirma, guarda los códigos.
- [ ] Cierra sesión y vuelve a entrar → pide el código de 6 dígitos.
- [ ] **Usuarios** (gerente): invita a un usuario; revisa el correo en `storage/logs/laravel.log`, abre el enlace y crea la contraseña.
- [ ] Desactiva a `asesor.medellin` y trata de entrar con él → "credenciales no coinciden".
- [ ] Intenta desactivarte a ti mismo → error.
- [ ] Como **director.bogota**: *Usuarios* muestra solo gente de Bogotá y sin botones de edición.
- [ ] Como **admin**: al invitar, el rol "Finanzas" no aparece (no puede dar permisos que no tiene).

### Fase 1.3 — Audit (usuario: gerente)
- [ ] *Auditoría → Cambios*: aparecen tus cambios de agencia/sucursales con valor anterior → nuevo.
- [ ] *Seguridad*: aparecen tus inicios de sesión y un intento fallido (provócalo con una contraseña errada).

### Fase 1.4 — Workflow
- [ ] Como **asesor.bogota**: *Tareas* muestra 2 tareas, una **vencida**. Termínala y reábrela. Crea una tarea con recordatorio en 1 minuto.
- [ ] Ejecuta `php artisan workflow:send-task-reminders` después de ese minuto; el recordatorio queda en la tabla `notifications`.
- [ ] Como **director.bogota**: *Aprobaciones* muestra el descuento de San Andrés, no el de Medellín ni el reembolso. Recházalo sin nota → error; con nota → rechazado.
- [ ] Como **finanzas**: aparece el reembolso; apruébalo.
- [ ] Como **asesor.bogota**: *Aprobaciones → Mis solicitudes* muestra los estados y la nota de rechazo.

### Fase 1.5 — Crm
- [ ] Como **asesor.bogota**: *Clientes* muestra a Laura Pérez con el documento enmascarado (52••••56). Busca por `52.123.456` → la encuentra.
- [ ] Crea un cliente sin marcar la autorización de datos → error. Con autorización → queda registrado con su evidencia.
- [ ] Crea otro cliente con el mismo documento escrito distinto (`52-123-456`) → "ya existe".
- [ ] Como **gerente**: en la ficha de Laura escribe un motivo y pulsa "Mostrar número de documento" → aparece y queda en *Auditoría → Accesos a datos sensibles*.
- [ ] Tomás (8 años) aparece como **Niño** y con alerta de pasaporte por vencer.
- [ ] *Embudo de ventas*: abre "Familia Gómez", registra una llamada → pasa a Contactado. Márcalo perdido sin motivo → error.
- [ ] "Carlos Ruiz" (cotizado): márcalo ganado eligiendo a Laura Pérez como cliente.

### Fase 1.6 — Suppliers
- [ ] Como **asesor.bogota**: *Proveedores* lista 4 proveedores con su situación (Habilitado, RNT por vencer, RNT vencido). No ve "Nuevo proveedor".
- [ ] Marca "Solo los que requieren atención" → quedan Tours Ciudad Amurallada y Transportes Sabana.
- [ ] Como **finanzas**: crea un proveedor colombiano turístico sin RNT → error; con RNT → se guarda.
- [ ] En "Hotel Caribe Real" agrega una comisión de hoteles del 10 % desde hoy; intenta otra que se cruce → error. Termínala y crea la nueva.
- [ ] Agrega una cuenta bancaria → aparece enmascarada; con un motivo, "Mostrar número" → aparece y queda en *Auditoría → Accesos a datos sensibles*.
- [ ] Como **gestor de producto** (crea uno desde Usuarios): administra proveedores pero no ve la sección de cuentas bancarias.
