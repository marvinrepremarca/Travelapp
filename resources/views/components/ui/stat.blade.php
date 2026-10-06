{{-- Tarjeta de indicador: valor, comparación opcional y enlace al listado que lo explica (drill-down). --}}
@props(['label', 'value', 'hint' => null, 'href' => null, 'tone' => null])
<div {{ $attributes->class(['flex flex-col gap-xs rounded-card border border-border bg-surface p-md shadow-card']) }}>
    <p class="text-caption text-text-subtle">{{ $label }}</p>
    <p @class(['text-heading-2 font-semibold', 'text-danger' => $tone === 'danger', 'text-success' => $tone === 'success'])>{{ $value }}</p>
    @if ($hint)<p class="text-caption text-text-subtle">{{ $hint }}</p>@endif
    @if ($href)<a href="{{ $href }}" wire:navigate class="text-caption font-medium text-brand underline">{{ __('reports.see_detail') }}<span class="sr-only"> · {{ $label }}</span></a>@endif
</div>
