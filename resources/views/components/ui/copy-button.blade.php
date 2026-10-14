{{--
    Copia un texto al portapapeles y confirma visualmente ("¡Copiado!") y para lectores de pantalla (aria-live).
    - text: valor a copiar · label: texto del botón
--}}
@props(['text', 'label' => null])
<span x-data="{ copied: false, timer: null }" class="inline-flex items-center gap-sm">
    <x-ui.button variant="secondary" type="button" {{ $attributes }}
        x-on:click="navigator.clipboard.writeText(@js($text)).then(() => { copied = true; clearTimeout(timer); timer = setTimeout(() => copied = false, @js(config('travel.ui.copy_feedback_ms'))) })">
        <x-ui.icon x-show="! copied" name="copy" />
        <x-ui.icon x-show="copied" x-cloak name="check" class="text-success" />
        <span x-text="copied ? @js(__('shared.copied')) : @js($label ?? __('shared.copy'))">{{ $label ?? __('shared.copy') }}</span>
    </x-ui.button>
    <span class="sr-only" aria-live="polite" x-text="copied ? @js(__('shared.copied')) : ''"></span>
</span>
