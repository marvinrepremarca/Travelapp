@props(['title', 'description' => null])
<div {{ $attributes->class(['flex flex-col items-center gap-sm rounded-card border border-dashed border-border p-xl text-center']) }}>
    <p class="text-heading-3 font-semibold">{{ $title }}</p>
    @if ($description)
        <p class="text-text-subtle">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
