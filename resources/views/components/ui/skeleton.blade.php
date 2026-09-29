@props(['lines' => 3])
<div {{ $attributes->class(['flex animate-pulse flex-col gap-sm']) }} role="status" aria-live="polite">
    <span class="sr-only">{{ __('shared.loading') }}</span>
    @foreach (range(1, $lines) as $line)
        <div class="h-md rounded-control bg-muted" wire:key="skeleton-{{ $line }}"></div>
    @endforeach
</div>
