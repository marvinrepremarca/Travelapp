---
name: ux-ui-accessibility
description: Criterios de UX/UI y accesibilidad (WCAG 2.2 AA) para backoffice, portal B2C, portal del viajero, portal B2B y corporativo. Úsala al diseñar una pantalla o flujo nuevo, un buscador, un checkout, un formulario, tablas, mensajes de error, estados vacíos, notificaciones, o al revisar usabilidad.
---

# UX/UI y accesibilidad

## Objetivos por usuario

| Usuario | Lo que más valora | Implicaciones |
|---|---|---|
| Asesor de viajes | Velocidad: cotizar en minutos, ver todo del cliente en un lugar | Atajos de teclado, búsqueda global (`Ctrl+K`) por cliente/expediente/localizador, vista 360° del expediente, acciones rápidas, autocompletar pasajeros frecuentes |
| Operaciones | No olvidar nada | Tablero de plazos y pendientes por prioridad, filtros guardados, confirmaciones masivas |
| Finanzas | Exactitud y trazabilidad | Desgloses, conciliación lado a lado, exportaciones, historial de cada cifra |
| Gerente | Visión del negocio | Dashboard de KPIs con comparativos y drill-down |
| Viajero (B2C) | Confianza y claridad | Precio total desde el inicio (sin sorpresas), políticas visibles antes de pagar, móvil primero, documentos a un toque |
| Corporativo | Cumplimiento sin fricción | Política visible en cada opción, aprobar en un clic desde el móvil |

## Antes de construir una pantalla, define
1. Tarea principal y la acción primaria (una sola por vista).
2. Datos mínimos a mostrar y cuáles van en detalle progresivo.
3. Estados: carga (skeleton), vacío (con acción sugerida), error (qué pasó y qué hacer), éxito, parcial (proveedor caído: "mostramos resultados de 3 de 4 proveedores").
4. Permisos: qué cambia según el rol (ocultar, no deshabilitar sin explicación).
5. Versión móvil.

## Patrones clave del dominio
- **Precio:** muestra siempre total, por persona/noche cuando ayude, qué incluye, impuestos incluidos vs. a pagar en destino, y la moneda. En backoffice, margen con permiso.
- **Políticas de cancelación:** en lenguaje claro con fechas concretas en la zona del cliente ("Cancelación gratis hasta el 12 oct 2026, 23:59 hora de Bogotá").
- **Plazos:** chips con cuenta regresiva y tono por urgencia (texto + icono, no solo color).
- **Cambios de precio o disponibilidad** durante el checkout: modal explícito con el precio anterior y el nuevo, y opción de continuar o volver.
- **Confirmaciones destructivas** (cancelar, reembolsar, anular): resumen del impacto económico antes de confirmar y escribir el motivo.
- **Itinerario:** línea de tiempo por día con iconos por tipo de servicio, horas locales y conectores de traslado.
- **Formularios de pasajeros:** explicar por qué se pide cada dato sensible; guardar como perfil reutilizable con consentimiento.

## Escritura (microcopy)
Español neutro, tuteo o usted según la configuración de la agencia ⚙, frases cortas, verbos en los botones ("Confirmar reserva", no "Aceptar"), errores sin culpa y con solución, sin jerga técnica ni códigos de proveedor.

## Accesibilidad (WCAG 2.2 AA)
Contraste 4.5:1 texto / 3:1 componentes; foco visible y orden lógico; objetivo táctil ≥ 24 px (44 px en portal móvil); navegable solo con teclado (modales con trampa de foco y `Esc`); `aria-live` para resultados de búsqueda y toasts; tablas con `<th scope>`; imágenes con `alt` (decorativas `alt=""`); no depender de hover; respetar `prefers-reduced-motion`; formularios sin límite de tiempo oculto (si hay expiración de oferta, avisar y permitir extender).

## Internacionalización
Textos en `lang/es` (preparado para `en`, `pt`), formatos por locale, nombres de ciudades y países traducidos desde catálogos, soporte de nombres con caracteres no latinos en perfiles (aunque el aéreo exija transliteración).

## Checklist de cumplimiento
- [ ] Una acción primaria clara por vista; flujo sin pasos innecesarios.
- [ ] Precio total, moneda, impuestos y políticas visibles antes de comprometer al usuario.
- [ ] Confirmaciones destructivas muestran impacto económico y piden motivo.
- [ ] Microcopy claro, sin jerga ni códigos de proveedor.
- [ ] Contraste AA, foco visible, navegable por teclado, `aria-*` en errores y regiones vivas.
- [ ] Estado nunca solo por color; objetivos táctiles adecuados; `prefers-reduced-motion`.
- [ ] Versión móvil revisada.
