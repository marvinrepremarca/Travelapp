<div class="flex flex-col gap-lg" role="status">
    <div class="grid gap-md md:grid-cols-4">
        @foreach (range(1, 4) as $card)
            <x-ui.skeleton :lines="2" wire:key="placeholder-{{ $card }}" />
        @endforeach
    </div>
    <x-ui.skeleton :lines="6" />
    <span class="sr-only">{{ __('reports.loading') }}</span>
</div>
