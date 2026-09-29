# Reglas: seguridad (siempre activas)

Detalle en la skill `security-owasp`. Mínimo obligatorio en todo cambio:

1. **Alcance de datos:** los listados aplican el scope de visibilidad del rol (`own` / `branch` / `all`) y las Policies lo verifican en cada recurso. Un recurso fuera del alcance responde **404**, no 403.
2. **Control de acceso:** Policy en cada acción; permisos como enum; IDs públicos ULID. Viajeros y clientes corporativos del portal solo ven lo suyo.
3. **Validación estricta** en FormRequest/Form; `Rule::enum`, `Rule::exists` con las condiciones que correspondan. Precios, totales, descuentos y comisiones **siempre se recalculan en el servidor**.
4. **Asignación masiva:** `$fillable` explícito; `status`, montos, `branch_id`, responsable, comisiones y roles nunca vienen del request.
5. **Pagos (PCI DSS):** solo tokens / campos alojados de la pasarela. Nunca PAN ni CVV en BD, logs, colas ni correos. Webhooks con firma verificada e idempotentes.
6. **Datos personales:** pasaportes, documentos, fechas de nacimiento, datos de menores y de salud cifrados y enmascarados en UI y logs. Consentimiento y finalidad registrados (Ley 1581 de 2012, GDPR para viajeros de la UE).
7. **Salida:** `{{ }}` siempre; `{!! !!}` solo en el componente de Markdown sanitizado.
8. **SQL:** Eloquent/Query Builder con bindings; ordenamientos y columnas dinámicas por lista blanca.
9. **Integraciones:** credenciales de proveedores cifradas; hosts salientes en lista blanca (anti-SSRF); timeouts siempre.
10. **Secretos** fuera del código y los logs (`#[\SensitiveParameter]`). **Errores:** sin trazas al usuario, falla cerrado, nunca `catch` vacío.
11. **Dependencias:** ninguna nueva sin aprobación; `composer audit` y `npm audit` limpios.
