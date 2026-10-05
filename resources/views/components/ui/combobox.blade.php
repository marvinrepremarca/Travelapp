{{--
    Autocompletar accesible (patrón combobox de WAI-ARIA): el usuario escribe el nombre natural y elige una sugerencia.
    - model:   propiedad Livewire con el texto escrito (p. ej. lookup.origin)
    - name:    clave del error de validación (p. ej. criteria.origin)
    - field:   campo que se envía a choose(field, value)
    - options: list<PlaceSuggestion> para este campo (vacía si no está activo)
--}}
@props(['model', 'name', 'field', 'options' => [], 'hint' => false])
@php
    $id = $attributes->get('id', $name);
    $listbox = "{$id}-listbox";
    $invalid = $errors->has($name);
    $describedBy = collect([$hint ? "{$name}-hint" : null, $invalid ? "{$name}-error" : null])->filter()->implode(' ');
@endphp
<div class="relative" x-data="combobox" x-on:click.outside="close()" wire:key="combobox-{{ $id }}">
    <input
        type="text"
        id="{{ $id }}"
        name="{{ $model }}"
        role="combobox"
        autocomplete="off"
        aria-autocomplete="list"
        aria-controls="{{ $listbox }}"
        x-bind:aria-expanded="open && hasOptions() ? 'true' : 'false'"
        x-bind:aria-activedescendant="activeId('{{ $listbox }}')"
        @if ($invalid) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        wire:model.live.debounce.300ms="{{ $model }}"
        x-on:input="reset()"
        x-on:focus="open = true"
        x-on:keydown.arrow-down.prevent="move(1)"
        x-on:keydown.arrow-up.prevent="move(-1)"
        x-on:keydown.enter="pick($event)"
        x-on:keydown.escape="close()"
        {{ $attributes->except(['id', 'name'])->class([
            'w-full rounded-control border bg-surface px-md py-sm text-body text-text',
            'border-danger' => $invalid,
            'border-border' => ! $invalid,
        ]) }}
    >
    @if ($options !== [])
        <ul id="{{ $listbox }}" role="listbox" x-ref="listbox" x-show="open" x-cloak
            class="absolute z-dropdown mt-xs flex w-full flex-col overflow-hidden rounded-control border border-border bg-surface shadow-card">
            @foreach ($options as $index => $option)
                <li id="{{ $listbox }}-{{ $index }}" role="option" wire:key="{{ $listbox }}-{{ $option->value }}"
                    x-bind:aria-selected="active === {{ $index }} ? 'true' : 'false'"
                    x-bind:class="active === {{ $index }} ? 'bg-muted' : ''"
                    x-on:mousedown.prevent
                    x-on:click="close()"
                    wire:click="choose(@js($field), @js($option->value))"
                    class="flex cursor-pointer flex-col px-md py-sm hover:bg-muted">
                    <span class="font-medium">{{ $option->label }}</span>
                    <span class="text-caption text-text-subtle">{{ $option->detail }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
