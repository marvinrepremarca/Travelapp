@props(['caption'])
<div class="overflow-x-auto rounded-card border border-border bg-surface">
    <table {{ $attributes->class(['w-full text-left text-body']) }}>
        <caption class="sr-only">{{ $caption }}</caption>
        @isset($head)
            <thead class="bg-muted text-caption uppercase text-text-subtle">{{ $head }}</thead>
        @endisset
        <tbody class="divide-y divide-border">{{ $slot }}</tbody>
    </table>
</div>
