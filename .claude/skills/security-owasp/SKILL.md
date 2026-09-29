---
name: security-owasp
description: Seguridad de la aplicación con OWASP Top 10:2025, OWASP ASVS, PCI DSS para pagos y protección de datos personales de viajeros. Úsala al implementar autenticación, autorización, control de acceso por alcance, pagos, carga de archivos (pasaportes), integraciones y webhooks, exportaciones, logs, o al revisar la seguridad de un cambio.
---

# Seguridad

Activos más valiosos: **datos de pasajeros** (pasaportes, fechas de nacimiento, menores), **dinero** (pagos, reembolsos, tarjetas virtuales) y **credenciales de proveedores** (una credencial de GDS robada permite emitir tiquetes con cargo a la agencia).

## A01 · Broken Access Control (incluye SSRF)
- Alcance en tres capas: scope de visibilidad (own/branch/all) + Policy + test "fuera de alcance → 404" en cada endpoint.
- Portales: el viajero accede con enlace mágico firmado o cuenta; solo ve expedientes donde es titular o pasajero. Enlaces de documentos firmados y con expiración.
- IDOR: ULIDs + Policy; nunca confíes en que el ID "no se adivina".
- SSRF: las URLs de proveedores salen de configuración (lista blanca de hosts); nunca de input del usuario. Webhooks salientes a URLs de clientes: bloquear IPs privadas/metadata.

## A02 · Security Misconfiguration
`APP_DEBUG=false` en producción, cabeceras (CSP estricta con nonce, HSTS, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`), cookies `Secure`/`HttpOnly`/`SameSite=Lax`, CORS solo para orígenes de portales registrados, Horizon/Pulse/Telescope protegidos por rol de plataforma.

## A03 · Supply Chain
Dependencias aprobadas y fijadas (`composer.lock`, `package-lock.json`), `composer audit`/`npm audit` en CI, SDKs de proveedores revisados antes de adoptarlos (preferir HTTP client propio si el SDK es pesado o abandonado).

## A04 · Cryptographic Failures
- Cast `encrypted` para documentos de viaje, fechas de nacimiento, notas médicas, credenciales de proveedores y tokens de pasarela. Hash HMAC (`hash_hmac` con clave dedicada) para búsqueda exacta.
- Rotación de `APP_KEY` planificada (`APP_PREVIOUS_KEYS`). TLS 1.2+ en todas las integraciones.

## A05 · Injection
Eloquent con bindings; `orderBy` por lista blanca; nada de `DB::raw` con input; escapar en XML/SOAP de proveedores con builders, nunca concatenación; plantillas de correo/WhatsApp sin evaluación de código; CSV/Excel exportado protegido contra fórmulas (`=`, `+`, `-`, `@` iniciales).

## A06 · Insecure Design
- Precios recalculados en servidor; límites de negocio (descuento máximo por rol, reembolso ≤ pagado, no vender bajo neto).
- Anti-fraude en B2C: velocidad de intentos de pago, 3DS obligatorio, reserva de vuelos solo tras pago capturado ⚙, alertas por reservas de alto valor y última hora.
- Doble aprobación para operaciones de alto impacto ⚙.

## A07 · Authentication Failures
Fortify con 2FA obligatorio para roles internos ⚙, políticas de contraseñas (`uncompromised`), rate limiting en login y en enlaces mágicos, bloqueo progresivo, sesiones invalidadas al cambiar contraseña o rol, SSO (SAML/OIDC) para corporativos cuando se apruebe.

## A08 · Software or Data Integrity
Webhooks entrantes con firma y timestamp (anti-replay); ledger inmutable; documentos generados con hash; colas con payload firmado (serialización de Laravel) y sin datos sensibles en claro.

## A09 · Logging & Alerting
Canal `security` (login fallidos, cambios de rol, exportaciones de datos personales, accesos a documentos completos, reembolsos, cambios de credenciales de proveedores). Logs JSON con `correlation_id`, `branch_id`, `user_id`; **nunca** PAN, CVV, documentos, contraseñas ni tokens (processor de redacción en Monolog). Alertas a Sentry/Slack.

## A10 · Exceptional Conditions
Falla cerrado: si no se puede verificar el pago, el permiso o la firma → denegar. Estados desconocidos de proveedor → `on_request` + tarea humana, nunca asumir. Timeouts en todo.

## PCI DSS
Objetivo SAQ-A: el formulario de tarjeta es de la pasarela (hosted/iframe/redirect); el sistema solo guarda token, marca, últimos 4 dígitos y vencimiento. Para pagar proveedores con tarjeta, usar tarjetas virtuales del emisor vía API, nunca almacenar la tarjeta corporativa del cliente en claro.

## Datos personales
Minimización (pide solo lo que el servicio exige), consentimiento por finalidad con versión del texto aceptado, enmascarado por defecto en UI (`AB•••••23`) con botón "mostrar" auditado, exportación y supresión (anonimización) del titular, retención ⚙ y purga programada.

## Archivos
Pasaportes/soportes: validación MIME real y tamaño, disco privado cifrado, nombre aleatorio, escaneo antivirus cuando esté disponible, descarga por controlador autorizado con URL temporal.

## Checklist de cumplimiento
- [ ] ¿Un usuario fuera de alcance (otro asesor, otra sucursal, otro viajero) puede leer o modificar esto? (test)
- [ ] ¿Un rol inferior puede ejecutarlo? (test 403)
- [ ] ¿Algún monto viene del cliente sin recalcular?
- [ ] ¿Hay datos personales o secretos en logs, colas, excepciones o respuestas?
- [ ] ¿Las llamadas externas tienen timeout, idempotencia y firma (webhooks)?
- [ ] ¿La acción queda auditada?
- [ ] ¿Headers, cookies y CORS según A02? ¿Dependencias auditadas?
- [ ] ¿Datos personales enmascarados en UI y con consentimiento registrado?
- [ ] ¿Archivos subidos validados, en disco privado y descargados vía controlador autorizado?
