---
name: frontend-ui
description: Implementación de interfaces con Blade, Livewire 3, Alpine.js y Tailwind CSS v4 (tokens de marca, componentes x-ui, formularios, tablas, buscadores, wizards de reserva, rendimiento de frontend). Úsala al crear o modificar vistas, componentes Livewire, CSS o JS.
---

# Frontend: Blade + Livewire 3 + Alpine + Tailwind v4

## Organización
```
resources/css/app.css             # @import "tailwindcss"; @theme { tokens }
resources/views/components/ui/     # Design system: button, input, select, date-range, money, badge, card,
                                   # table, modal, drawer, tabs, stepper, empty-state, skeleton, toast, timeline
resources/views/layouts/           # backoffice, portal (B2C/viajero), b2b, print (PDF)
resources/js/components/           # Alpine.data()
app/Modules/*/Livewire/            # Componentes de pantalla
```

## Tokens de diseño centralizados (`resources/css/tokens.css`)

Un solo archivo gobierna toda la apariencia. `app.css` solo hace `@import "tailwindcss"; @import "./tokens.css";`.

```css
@theme {
  /* Color: marca (sobrescribible desde la administración) y semánticos */
  --color-brand: var(--brand-primary, oklch(55% 0.15 250));
  --color-brand-contrast: var(--brand-primary-contrast, oklch(99% 0 0));
  --color-accent: var(--brand-accent, oklch(70% 0.15 60));
  --color-surface: oklch(99% 0 0);
  --color-muted: oklch(96% 0.005 250);
  --color-border: oklch(90% 0.01 250);
  --color-text: oklch(22% 0.02 250);
  --color-text-subtle: oklch(45% 0.02 250);
  --color-success: oklch(62% 0.15 150);
  --color-warning: oklch(75% 0.15 75);
  --color-danger: oklch(58% 0.2 25);
  --color-info: oklch(60% 0.12 240);

  /* Tipografía */
  --font-sans: var(--brand-font, "Inter"), system-ui, sans-serif;
  --font-display: var(--brand-font-display, var(--font-sans));
  --text-caption: 0.75rem;   --text-caption--line-height: 1rem;
  --text-body: 0.9375rem;    --text-body--line-height: 1.5rem;
  --text-heading-3: 1.125rem; --text-heading-2: 1.375rem; --text-heading-1: 1.75rem;

  /* Espaciado (margin, padding, gap): única escala permitida */
  --spacing: 0.25rem;               /* base de la escala numérica de Tailwind */
  --spacing-xs: 0.25rem; --spacing-sm: 0.5rem; --spacing-md: 1rem;
  --spacing-lg: 1.5rem;  --spacing-xl: 2rem;   --spacing-2xl: 3rem;
  --spacing-gutter: 1rem;           /* margen lateral de página */
  --spacing-section: 2.5rem;        /* separación entre secciones */

  /* Forma, elevación, movimiento */
  --radius-control: 0.5rem; --radius-card: 0.75rem;
  --shadow-card: 0 1px 2px oklch(0% 0 0 / 0.06);
  --ease-standard: cubic-bezier(0.2, 0, 0, 1);
}
```

- Uso: `p-md`, `px-gutter`, `mt-section`, `gap-sm`, `text-body`, `text-heading-2`, `rounded-card`, `shadow-card`, `bg-brand`.
- **Administración de la marca:** la pantalla "Apariencia" (módulo `Organization`) permite editar colores de marca y fuentes permitidas; guarda en configuración, valida contraste AA y el layout emite `<style>:root{--brand-primary:…}</style>` desde esos valores escapados. Espaciados y tipografía base solo cambian en `tokens.css` (decisión de diseño, no de usuario).
- Modo oscuro redefiniendo los mismos tokens bajo `[data-theme=dark]`; los componentes no cambian.
- Los documentos PDF (vouchers, itinerarios) usan los mismos tokens.

## Tokens de marca
```css
@theme {
  --color-brand: var(--agency-brand, oklch(55% 0.15 250));
  --color-brand-contrast: var(--agency-brand-contrast, oklch(99% 0 0));
  --color-surface: oklch(99% 0 0);
  --color-muted: oklch(96% 0.005 250);
  --color-border: oklch(90% 0.01 250);
  --color-text: oklch(22% 0.02 250);
  --color-success: …; --color-warning: …; --color-danger: …; --color-info: …;
  --font-sans: "Inter", system-ui, sans-serif;
  --radius-card: 0.75rem;
}
```
El layout inyecta `--agency-brand` desde la configuración de la agencia (validada por contraste AA al guardarla), para que el portal público y los documentos lleven su marca. Modo oscuro por tokens, nunca por clases sueltas.

## Componentes con variantes seguras
```blade
@props(['tone' => 'neutral'])
@php
$classes = match ($tone) {
    'success' => 'bg-success/10 text-success',
    'warning' => 'bg-warning/10 text-warning',
    'danger'  => 'bg-danger/10 text-danger',
    default   => 'bg-muted text-text',
};
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium', $classes]) }}>{{ $slot }}</span>
```
Los enums exponen `tone()` y `label()` para que la vista no decida colores ni textos.

## Livewire: patrones
- **Buscador** (hotel/vuelo/actividad): formulario con validación en vivo mínima → al enviar, `SearchResults` `#[Lazy]` con skeleton; filtros y orden en el servidor sobre el resultado cacheado; URL sincronizada con `#[Url]` para compartir la búsqueda.
- **Wizard de reserva/checkout:** pasos (servicio → pasajeros → extras → pago → confirmación) con `x-ui.stepper`; el estado vive en una entidad borrador en servidor, no solo en el componente; revalidación de precio antes del pago con aviso claro si cambió.
- **Tablas de backoffice:** filtros persistidos en URL, paginación, columnas ordenables por lista blanca, acciones masivas con confirmación, exportación en cola.
- **Tiempo real** (tablero de plazos, estado de pagos): `wire:poll.30s` o broadcasting (Reverb) cuando esté aprobado.
- Nunca pongas `Money`, precio o `branch_id` como propiedad pública editable; usa `#[Locked]` o recalcula.

## Formularios
- `x-ui.field` envuelve label, control, ayuda y error con `aria-describedby`.
- Campos de pasajero: nombres en mayúsculas sin tildes para aéreo (con explicación), selector de país con búsqueda, fecha de nacimiento con máscara y validación de edad por servicio.
- Montos con `x-ui.money-input` (moneda visible, separadores locales) y fechas con `x-ui.date-range` (noches calculadas, deshabilita fechas sin disponibilidad).

## Documentos imprimibles (vouchers, itinerarios, cotizaciones)
Layout `print` con tokens de la agencia, sin JS, probado en A4 y carta; misma vista para web y PDF.

## Rendimiento
Vite con code splitting, imágenes responsive (`srcset`, `loading="lazy"`, WebP/AVIF desde el pipeline de medios), fuentes con `font-display: swap`, cero dependencias JS pesadas sin aprobación. Presupuesto: LCP < 2.5 s en 4G para portal público; INP < 200 ms.

## Checklist de cumplimiento
- [ ] Colores, tipografía, espaciados, radios y sombras solo desde `tokens.css`; cero hex, `style=""`, `[...]` arbitrarios o clases de paleta cruda.
- [ ] Márgenes y rellenos con la escala de tokens (`xs`…`2xl`, `gutter`, `section`).
- [ ] Componentes `x-ui` reutilizados; clases dinámicas completas vía `match`.
- [ ] Todo texto con `__()`; formato de dinero y fechas con los helpers.
- [ ] Livewire: autoriza, delega en Actions, `#[Locked]` en IDs/montos, `wire:key`, `wire:loading`.
- [ ] Estados de carga, vacío, error y parcial implementados.
- [ ] Responsive (360 px) y probado en modo oscuro si aplica.
