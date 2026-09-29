@props(['variant' => 'primary', 'type' => 'button'])
@php
$classes = match ($variant) {
    'secondary' => 'bg-surface text-text border border-border hover:bg-muted',
    'danger' => 'bg-danger text-brand-contrast hover:opacity-90',
    'ghost' => 'bg-transparent text-text hover:bg-muted',
    default => 'bg-brand text-brand-contrast hover:opacity-90',
};
@endphp
<button type="{{ $type }}" {{ $attributes->class(['inline-flex items-center justify-center gap-sm rounded-control px-md py-sm text-body font-medium transition disabled:cursor-not-allowed disabled:opacity-50', $classes]) }}>
    {{ $slot }}
</button>
