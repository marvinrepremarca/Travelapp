<div class="flex flex-col gap-lg">
    <header class="flex flex-col gap-xs">
        <h1 class="text-heading-1 font-semibold">{{ __('portal.shop.title') }}</h1>
        <p class="text-text-subtle">{{ __('portal.shop.hint') }}</p>
    </header>

    @if ($products === [])
        <x-ui.empty-state :title="__('portal.shop.empty')" />
    @else
        <ul class="grid gap-lg md:grid-cols-2 lg:grid-cols-3">
            @foreach ($products as $product)
                <li wire:key="product-{{ $product->ulid }}">
                    <x-ui.card class="flex h-full flex-col gap-sm">
                        <p class="text-caption text-text-subtle">{{ $product->type->label() }} · {{ $product->destinationCity }}</p>
                        <h2 class="text-heading-3 font-semibold">{{ $product->name }}</h2>
                        @if ($product->description)
                            <p class="text-text-subtle">{{ \Illuminate\Support\Str::limit($product->description, config('travel.portal.shop_excerpt_length')) }}</p>
                        @endif
                        <p class="mt-auto">
                            @if ($prices[$product->ulid])
                                <span class="text-caption text-text-subtle">{{ __('portal.shop.from') }}</span>
                                <span class="text-heading-3 font-semibold">{{ $prices[$product->ulid] }}</span>
                                <span class="text-caption text-text-subtle">{{ __('portal.shop.per_person') }}</span>
                            @else
                                <span class="text-text-subtle">{{ __('portal.shop.price_on_request') }}</span>
                            @endif
                        </p>
                        <p class="text-caption text-text-subtle">{{ trans_choice('portal.shop.departures_count', count($product->departures), ['count' => count($product->departures)]) }}</p>
                        <x-ui.link-button :href="route('portal.shop.product', $product->ulid)">{{ __('portal.shop.see') }}</x-ui.link-button>
                    </x-ui.card>
                </li>
            @endforeach
        </ul>
    @endif
</div>
