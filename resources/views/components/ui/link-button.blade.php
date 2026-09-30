@props(['href', 'variant' => 'primary'])
@php
$classes = match ($variant) {
    'secondary' => 'border border-border bg-surface text-text hover:bg-muted',
    default => 'bg-brand text-brand-contrast hover:opacity-90',
};
@endphp
<a href="{{ $href }}" wire:navigate {{ $attributes->class(['inline-flex items-center justify-center rounded-control px-md py-sm text-body font-medium', $classes]) }}>
    {{ $slot }}
</a>
