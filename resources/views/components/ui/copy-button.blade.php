{{--
    Copia un texto al portapapeles y confirma visualmente ("¡Copiado!") y para lectores de pantalla (aria-live).
    - text: valor a copiar · label: texto del botón
    Los valores viajan en x-data (elemento HTML simple): dentro de los atributos de otro componente Blade no se compilan las directivas como @js.
--}}
@props(['text', 'label' => null])
<span class="inline-flex items-center gap-sm"
    x-data="{
        copied: false,
        timer: null,
        text: @js($text),
        label: @js($label ?? __('shared.copy')),
        done: @js(__('shared.copied')),
        wait: @js(config('travel.ui.copy_feedback_ms')),
        copy() {
            navigator.clipboard.writeText(this.text).then(() => {
                this.copied = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.copied = false, this.wait);
            });
        },
    }">
    <x-ui.button variant="secondary" type="button" x-on:click="copy()" {{ $attributes }}>
        <x-ui.icon x-show="! copied" name="copy" />
        <x-ui.icon x-show="copied" x-cloak name="check" class="text-success" />
        <span x-text="copied ? done : label">{{ $label ?? __('shared.copy') }}</span>
    </x-ui.button>
    <span class="sr-only" aria-live="polite" x-text="copied ? done : ''"></span>
</span>
