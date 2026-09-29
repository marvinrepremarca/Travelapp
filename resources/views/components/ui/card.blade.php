@props(['title' => null])
<section {{ $attributes->class(['rounded-card border border-border bg-surface p-lg shadow-card']) }}>
    @if ($title)
        <h2 class="mb-md text-heading-3 font-semibold">{{ $title }}</h2>
    @endif
    {{ $slot }}
</section>
