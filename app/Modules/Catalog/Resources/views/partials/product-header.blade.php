@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<x-ui.card>
    <div class="flex flex-col gap-md">
        <div class="flex flex-wrap items-center gap-sm">
            <x-ui.badge :tone="$product->is_active ? Tone::Success : Tone::Neutral">{{ $product->is_active ? __('catalog.active') : __('catalog.inactive') }}</x-ui.badge>
            <span class="text-caption text-text-subtle">{{ $product->code }} · {{ $product->product_type->label() }}</span>
        </div>
        <dl class="grid gap-md md:grid-cols-4">
            <div><dt class="text-caption text-text-subtle">{{ __('catalog.from_price') }}</dt>
                <dd class="font-medium">{{ $fromPrice ? __('catalog.from_price_value', ['amount' => $presenter->format($fromPrice)]) : __('catalog.no_from_price') }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.destination_city') }}</dt>
                <dd>{{ __('catalog.destination', ['city' => $product->destination_city, 'country' => $product->destination_country]) }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.timezone') }}</dt><dd>{{ $product->timezone }}</dd></div>
            <div><dt class="text-caption text-text-subtle">{{ __('catalog.fields.duration_minutes') }}</dt>
                <dd>{{ $product->duration_minutes ? __('catalog.duration', ['minutes' => $product->duration_minutes]) : '—' }}</dd></div>
        </dl>
        @if ($product->description)
            <p class="text-body">{{ $product->description }}</p>
        @endif
        @if ($canManage)
            <div class="flex justify-end gap-sm">
                <x-ui.button variant="ghost" wire:click="toggleActive" wire:loading.attr="disabled">
                    {{ $product->is_active ? __('catalog.deactivate') : __('catalog.activate') }}
                </x-ui.button>
                <x-ui.link-button :href="route('catalog.edit', $product)" variant="secondary">{{ __('shared.edit') }}</x-ui.link-button>
            </div>
        @endif
    </div>
</x-ui.card>
