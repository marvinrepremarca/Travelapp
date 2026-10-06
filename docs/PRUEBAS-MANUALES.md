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

**Rendimiento local (obligatorio para medir):** en `C:\xampp\php\php.ini` deja `zend_extension=opcache`, `opcache.enable=1` y `xdebug.mode=off`; reinicia `php artisan serve`. Con Xdebug en modo `coverage` cada pantalla tarda de 0,4 a 1 s; con OPcache y sin Xdebug, de 100 a 150 ms con sesión iniciada. Unos 100 ms son el piso del servidor de desarrollo de Windows (`/health` sin BD ni sesión ya tarda eso); la pantalla en sí toma de 10 a 40 ms. Si el antivirus de Windows analiza `C:\xampp` y la carpeta del proyecto, cada request es más lento. La cobertura enciende Xdebug sola (`composer test:coverage`). Si publicas bajo una carpeta en Apache, define `ASSET_URL=/<carpeta>` para que CSS y JS carguen desde ella.

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

## Orden recomendado para probar (igual al menú)

El menú está numerado por etapas; **Inicio** muestra la misma guía con la explicación de cada paso.

1. **Configurar la agencia** (como *gerente*): 1.1 Datos de la agencia → 1.2 Sucursales → 1.3 Usuarios y roles → 1.4 Parámetros → 1.5 Festivos.
2. **Proveedores y precios**: 2.1 Proveedores → 2.2 Tasas de cambio (registra la TRM del día) → 2.3 Reglas de precio → 2.4 Catálogo propio → 2.5 Simulador.
3. **Vender** (como *asesor*): 3.1 Clientes y viajeros → 3.2 Oportunidades → 3.3/3.4 Buscar vuelos u hoteles → 3.5 Cotizaciones (enviar y aceptar).
4. **Reservar, cobrar y operar**: 4.1 Expedientes → confirmar servicios, asignar pasajeros, *Pagos*, vouchers e itinerario.
5. **Seguimiento**: Tareas, Aprobaciones (finanzas aprueba reembolsos), Auditoría.

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

### Fase 2.1 — Pricing
- [ ] Como **finanzas**: *Tasas de cambio* → "Consultar TRM" trae la TRM del día (requiere internet). Registra una tasa manual USD→COP → prevalece sobre la oficial ese día.
- [ ] Como **gestor de producto**: *Reglas de precio* muestra el IVA 19 %. Crea un markup general del 10 % y otro del 12 % para hoteles; crea un fee de gestión de $30.000 por reserva.
- [ ] *Simulador*: neto 1.000.000 COP, hotel, 2 pasajeros → markup 120.000 (gana la regla de hoteles), fee 30.000, IVA 28.500 sobre el ingreso de la agencia, total 1.178.500 y margen 150.000.
- [ ] Repite con un tour → aplica la regla general del 10 %. Con neto en USD se muestra la tasa usada y su fecha.
- [ ] Como **asesor.bogota**: el simulador no muestra el margen y no ve *Reglas de precio* (403 si entra por URL).

### Fase 2.2 — Catalog (producto propio)
- [ ] Como **asesor.bogota**: *Catálogo* muestra 3 productos; filtra por "Traslado" y por texto "rosario". No ve "Nuevo producto" ni los formularios de temporadas/salidas.
- [ ] Abre "Pasadía Islas del Rosario": temporadas media y alta con neto adulto/niño/infante y dos salidas; la de 2 cupos muestra "2 de 2 disponibles".
- [ ] Como **gerente**: crea un producto (código, tipo, ciudad); intenta un código repetido → error.
- [ ] Agrega una temporada que se cruce con otra → error; una contigua → se guarda. Deja vacío el neto de niño → aparece "Sin tarifa".
- [ ] Programa una salida en una fecha pasada → error; la misma fecha y hora dos veces → error. Cierra la venta de una salida y reábrela.
- [ ] Abre "Cartagena esencial 3 días": itinerario día 1 (traslado + city tour), día 2 (Rosario), día 3 (traslado) y "Neto desde (adulto)" igual a la suma de los netos de adulto más bajos.
- [ ] Como **gerente**: agrega un componente al paquete; repite el mismo producto el mismo día → error. Quita un componente (pide confirmación).
- [ ] Edita "City tour Cartagena" y cámbialo a tipo Paquete → error (forma parte de un paquete).

### Fase 2.3 — Quotes (parte A)
- [ ] Como **asesor.bogota**: *Cotizaciones* muestra "Cartagena en familia" (enviada, con vigencia) y "Escapada a San Andrés" (borrador). Otro asesor no las ve; por URL → 404.
- [ ] Abre el borrador: agrega un ítem de catálogo (paquete Cartagena, edades "38, 8") y uno manual (hotel con neto y noches). El total sale del servidor; el margen no se muestra al asesor.
- [ ] Agrega la opción B vacía e intenta enviar → error "La opción B no tiene ítems". Quítala y envía: aparece la versión 1 con su vigencia.
- [ ] En la enviada, "Editar nueva versión" → vuelve a borrador; envía de nuevo → versión 2 (la 1 sigue en la lista).
- [ ] Registra la aceptación de la opción A con una nota → estado Aceptada.
- [ ] Como **gerente**: la misma cotización muestra neto, tasa y margen por ítem.

### Fase 2.3 — Quotes (parte B: enlace del cliente)
- [ ] En "Cartagena en familia" (enviada) copia el **enlace para el cliente** y ábrelo en una ventana privada: se ven opciones, itinerario por día y total, sin neto ni margen.
- [ ] Cambia una letra de la firma en la URL → 403. Acepta sin nombre o sin marcar condiciones → errores. Acepta bien → "¡Gracias!"; en el backoffice queda Aceptada por "Enlace del cliente".
- [ ] En otra cotización: envía, copia el enlace, "Editar nueva versión" y vuelve a abrir el enlace viejo → "Esta versión fue reemplazada".

### Fase 2.4 — Bookings (parte A)
- [ ] Como **asesor.bogota**: *Expedientes* muestra el de "Cartagena luna de miel" (en gestión): hotel confirmado (HCR-20451) y pasadía por solicitar.
- [ ] En la pasadía, "Gestionar" → Confirmado: pide código y salida (08:00 con cupos). Confirma → "Cupo apartado" y el expediente pasa a Confirmado; en el catálogo baja el cupo disponible.
- [ ] Cancela la pasadía con nota → vuelve el cupo. Rechaza el hotel con nota → "Requiere atención"; luego cancélalo → el estado se recalcula.
- [ ] En una cotización aceptada, "Crear expediente" → resumen y botón; al volver a entrar muestra el enlace al expediente existente. Otro asesor → 404.
- [ ] En un expediente, "Pasajeros" de un servicio: elige viajeros del cliente. Si no coinciden con lo cotizado (p. ej. un niño que cumple 12 antes del viaje) → error explicando la diferencia. Con la composición correcta → se muestran nombre, edad y tipo.
- [ ] En un servicio del expediente, "Política": escribe `30:0, 15:50, 7:100` → se muestra el resumen. Un tramo de 150 % → error. Marca "No reembolsable" → 100 %.
- [ ] Con el servicio confirmado, la ficha muestra "Si se cancela hoy: penalidad $…"; al elegir Cancelado aparece el aviso y, al guardar, "Penalidad registrada".

### Fase 2.5 — Documents (PDF)
- [ ] En una cotización enviada, "Descargar PDF (versión N)": membrete de la agencia, opciones, itinerario y totales, sin neto ni margen. En el enlace del cliente, "Descargar PDF".
- [ ] En el expediente, "Itinerario PDF": servicios vigentes por día con confirmaciones y pasajeros. En un servicio confirmado, "Voucher" con código y pasajeros. Un servicio sin confirmar no tiene voucher.
- [ ] Sube un logo en *Datos de la agencia* y vuelve a descargar: aparece el logo y el color de marca.

### Fase 2.6 — Búsqueda (parte A, proveedor Fake)
- [ ] *Buscar vuelos*: BOG → CTG, fecha futura, edades "35, 33" → 3 opciones con precio de venta (requiere tasa USD→COP del día) y neto. Destino `ERR` → aviso "Resultados parciales" y sin disponibilidad; destino `NON` → sin disponibilidad.
- [ ] *Buscar hoteles*: Cartagena, CO, 3 noches → 3 hoteles con régimen, cancelación y precio. Ciudad `agotado` → sin disponibilidad.
- [ ] Cambia `TRAVEL_FLIGHT_PROVIDERS` en `.env` (cuando estén Duffel/LiteAPI) y vuelve a buscar: cambian los proveedores sin tocar el código.
- [ ] Con una cotización en borrador, en *Buscar vuelos* elige la cotización y "Agregar a cotización" en una oferta → aparece como servicio con su venta.
- [ ] Envía, acepta y crea el expediente; asigna el pasajero al vuelo y "Gestionar → Confirmado": no pide código, reserva con el proveedor y muestra la referencia `FAKE-…`.
- [ ] Repite con destino `PRC`: al confirmar avisa "El proveedor cambió el precio" y el servicio sigue por solicitar.

### Fase 3.1 — Payments (parte A)
- [ ] En el expediente "Cartagena luna de miel", "Pagos": total, pagado (efectivo 500.000), por confirmar (transferencia 800.000) y saldo con fecha límite.
- [ ] Como **finanzas**: "Validar" la transferencia → pasa a pagado. Como asesor no aparece el botón.
- [ ] Genera un link de pago por 100.000, ábrelo en otra ventana y "Simular pago aprobado" → al recargar, el abono queda aprobado. Intenta cobrar más del saldo → error.
- [ ] Cancela un servicio pagado con política de penalidad: en "Pagos" aparece el saldo a favor y "Se puede devolver hasta…". Solicita un reembolso → queda "Solicitado".
- [ ] Como **finanzas**, en *Aprobaciones* aprueba el reembolso; en "Pagos" registra el comprobante → "Pagado al cliente" y el saldo queda en cero.

### Fase 3.2 — Finance (parte A: cuentas por pagar)
- [ ] Como **finanzas**, menú 4.2 *Cuentas por pagar*: aparece "Hotel Caribe Real" con el neto del hotel confirmado y su vencimiento según el crédito del proveedor.
- [ ] Confirma otro servicio con proveedor y vuelve: aparece su obligación. Cancela un servicio pendiente de pago → queda "Anulada".
- [ ] Selecciona obligaciones de un proveedor, escribe el comprobante y "Registrar pago" → pasan a "Pagada".
- [ ] Menú 4.3 *Caja de la sucursal* (asesor.bogota): caja abierta con base 200.000. Registra un abono en efectivo en un expediente → aparece como entrada. Registra una salida.
- [ ] Cierra con un valor contado distinto al esperado → el cierre muestra la diferencia. Intenta un abono en efectivo con la caja cerrada → error "No hay caja abierta".

### Fase 3.2 — Finance (parte C: rentabilidad)
- [ ] Como **finanzas** o **gerente**, menú 5.5 *Rentabilidad*: el mes actual muestra el total del período, por asesor, por sucursal y por expediente (venta, costo, margen %, comisión esperada, penalidades y utilidad).
- [ ] Cambia el mes a uno sin ventas → mensaje vacío. Como dueño, filtra por sucursal. Como **asesor**, la opción no aparece en el menú.

### Autocompletar de lugares y embudo kanban
- [ ] *Buscar vuelos*: escribe "bogo" en Origen → aparece "Bogotá (BOG)"; elige con el mouse o con flechas + Enter. En Destino escribe "cartagena" y busca sin elegir → se reconoce CTG. Escribe "nueva york" sin elegir → pide elegir de la lista (hay 3 aeropuertos).
- [ ] *Buscar hoteles*: escribe "cartag" en Ciudad y elige "Cartagena, Colombia" → el País se completa solo. Escribe "Villa de Leyva" (sin aeropuerto) y en País "colom" → elige Colombia → la búsqueda funciona.
- [ ] *Embudo de ventas*: las etapas aparecen lado a lado como tablero kanban (en el celular se desplazan en horizontal); con más leads que el límite aparece "Ver más en Nuevo".

### Fase 3.2 — Finance (parte D: conciliación bancaria)
- [ ] Como **finanzas**, menú 1.6 *Cuentas bancarias*: aparece "Corriente principal" con 2 movimientos por conciliar. Crea otra cuenta: deja campos vacíos → errores por campo; complétala → se guarda.
- [ ] Valida la transferencia TRX-DEMO-001 del expediente demo y abre 4.4 *Conciliación bancaria*: la línea de 800.000 sugiere el abono → "Conciliar". La comisión GMF no tiene contrapartida → escribe una nota e "Ignorar".
- [ ] Carga otra vez el mismo CSV → "Este extracto ya se cargó". Carga un CSV con una fecha en otro formato → error con el número de fila y no se carga nada.
- [ ] En la caja, registra una salida tipo "Consignación al banco" → aparece en *Movimientos del sistema sin reflejo en el banco*.

### Fase 3.3 — Invoicing (parte A: facturas)
- [ ] Como **finanzas**, menú 4.5 *Facturación* → *Listos para facturar*: aparece un expediente solo cuando todos sus servicios están confirmados y el cliente pagó el total.
- [ ] "Emitir factura" → se abre la factura FV-n con dos líneas por servicio: *Recaudo para terceros* (neto del proveedor, sin IVA) e *Ingreso propio* (markup/fee con su IVA). El total coincide con lo vendido.
- [ ] El documento del cliente aparece enmascarado ("terminado en …"). Intentar facturar de nuevo el expediente → "ya tiene factura".
- [ ] *Documentos emitidos*: busca por número, expediente o cliente y filtra por tipo.

### Fase 3.3 — Invoicing (parte B: notas)
- [ ] En una factura emitida, *Solicitar nota crédito*: pide más de lo que queda en una línea → error con el saldo disponible. Pide un valor válido con motivo → queda "Pendiente de aprobación".
- [ ] Menú 5.2 *Aprobaciones*: aprueba la nota crédito → se emite NC-n y el *Neto con notas* de la factura baja. Rechaza otra → queda "Rechazada" sin nota.
- [ ] *Emitir nota débito* con un cargo de ingreso propio y uno de recaudo para terceros → ND-n con IVA solo en el ingreso propio.

### Fase 3.6 — Communications (parte A: chat de WhatsApp para cotizar)
- [ ] Abre `/integrations/whatsapp-simulator` (simulador de la demo) y escribe "Hola": el bot saluda y pregunta el nombre. Responde nombre, destino, fecha de salida (dd/mm/aaaa), regreso (o "no") y número de viajeros → el bot envía el resumen.
- [ ] Prueba una fecha inválida o pasada → el bot la vuelve a pedir. Escribe "asesor" en cualquier paso → pasa a un asesor.
- [ ] Como **asesor**, menú *Conversaciones WhatsApp* → "Por atender": abre la conversación, revisa lo que recogió el bot y "Tomar conversación" → se crea el lead (canal WhatsApp) y el cliente recibe el aviso. Responde: el mensaje aparece en el simulador.
- [ ] Cierra la conversación y vuelve a escribir desde el simulador → empieza una nueva con el bot.

### Fase 3.6 — Communications (parte B: avisos automáticos)
- [ ] Cliente con teléfono y consentimiento de tratamiento de datos: envía una cotización → en el simulador de WhatsApp (con el teléfono del cliente) llega el enlace para verla y aceptarla.
- [ ] Genera un link de pago y registra un abono en efectivo → llegan el link y el comprobante. Una transferencia llega solo cuando finanzas la valida.
- [ ] Responde desde el simulador al aviso → el mensaje aparece en *Conversaciones WhatsApp* del asesor responsable.
- [ ] Un cliente sin consentimiento no recibe avisos.
