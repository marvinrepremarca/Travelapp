---
paths:
  - "resources/**/*.blade.php"
  - "app/Modules/**/Resources/views/**/*.blade.php"
  - "app/Modules/**/Livewire/**/*.php"
  - "app/View/**/*.php"
  - "resources/css/**/*.css"
  - "resources/js/**/*.js"
---

# Reglas: frontend (Blade + Livewire 3 + Alpine + Tailwind v4)

<tokens>
- **Única fuente de verdad del diseño:** `resources/css/tokens.css` (importado en `app.css` dentro de `@theme`). Ahí, y solo ahí, se definen colores, tipografías (familias, tamaños, pesos, interlineados), escala de espaciado (margin, padding, gap), radios, sombras, anchos de contenedor, breakpoints, z-index y duraciones de animación.
- Cambiar un color, una fuente o un espaciado en todo el sistema = cambiar **una línea** en `tokens.css`. Los colores de marca editables desde la administración sobrescriben solo sus variables CSS (`--brand-*`).
- Márgenes y rellenos solo con la escala de tokens (`p-md`, `mt-lg`, `gap-sm`, `px-gutter`); tipografía solo con los tokens de texto (`text-body`, `text-heading-2`). Nada de `p-[13px]`, `text-[15px]`, `mt-7` fuera de la escala.
- **Prohibido:** hexadecimales, `rgb()`, `oklch()`, `px`/`rem` sueltos en vistas o CSS de componentes, `style="..."`, valores arbitrarios de Tailwind (`[...]`) y clases de paleta cruda (`bg-blue-600`).
- Nombres semánticos (`bg-surface`, `text-danger`, `bg-brand`), no de paleta.
</tokens>

<componentes>
- Bloque de clases repetido 2+ veces → componente Blade `x-ui.*`. Usa primero el catálogo existente en `resources/views/components/ui`.
- Clases dinámicas completas vía `match`; nunca `bg-{{ $color }}`.
- Sin lógica de negocio ni queries en vistas.
</componentes>

<livewire>
- Componentes delgados: autorizan (`$this->authorize()`) y delegan en Actions.
- Las propiedades públicas son superficie de ataque: no expongas modelos con datos sensibles; usa `#[Locked]` en IDs, montos y sucursal.
- Formularios con `Livewire\Form`; la Action revalida todo. Nunca confíes en el cliente para precios ni disponibilidad.
- `wire:model.live` solo con debounce; `wire:loading` y botón deshabilitado en toda acción; `wire:key` en todo loop.
- Resultados de proveedores: `#[Lazy]` + skeletons; la página nunca espera bloqueada a un proveedor.
</livewire>

<alpine>
- `x-data` inline solo para estados triviales; lo demás con `Alpine.data()` en `resources/js/components/`. Datos del servidor con `@js()`. `x-cloak`. Nada de jQuery ni CDNs.
</alpine>

<textos_formato_accesibilidad>
- Todo texto con `__()`. Fechas, monedas y números con los helpers de formato del proyecto (locale y zona horaria de la agencia o del destino). Nunca formatees a mano.
- HTML semántico, `<label>` por campo, `aria-invalid` + `aria-describedby` en errores, foco visible, navegable por teclado, contraste AA. El estado nunca solo por color.
- Mobile-first: portal del viajero, buscador y checkout se diseñan primero para 360 px.
</textos_formato_accesibilidad>
