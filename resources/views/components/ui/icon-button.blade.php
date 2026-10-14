{{--
    Acción de fila con icono (tablas y listados). El texto va en aria-label y title (tooltip), así el botón es compacto y accesible.
    - icon: nombre en x-ui.icon · label: texto de la acción (incluye el registro si ayuda, p. ej. "Editar Ana Ruiz")
    - href: si viene, es un enlace; si no, un botón (wire:click, wire:confirm… van en los atributos)
    - tone: neutral | danger | success
--}}
@props(['icon', 'label', 'href' => null, 'tone' => 'neutral', 'navigate' => true])
@php
$classes = ['inline-flex items-center justify-center rounded-control p-xs transition hover:bg-muted focus-visible:outline-2 disabled:cursor-not-allowed disabled:opacity-50',
    match ($tone) {
        'danger' => 'text-danger',
        'success' => 'text-success',
        default => 'text-text-subtle hover:text-text',
    }];
@endphp
@if ($href !== null)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-ui.icon :name="$icon" />
    </a>
@else
    <button type="button" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->class($classes) }}>
        <x-ui.icon :name="$icon" />
    </button>
@endif
