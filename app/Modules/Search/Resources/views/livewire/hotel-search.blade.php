<div class="flex flex-col gap-lg">
    <x-ui.card>
        <form wire:submit="search" class="grid gap-md md:grid-cols-3 md:items-end" novalidate>
            <x-ui.field :label="__('search.hotels.fields.city')" for="criteria.city" :hint="__('search.hotels.city_hint')">
                <x-ui.combobox name="criteria.city" model="lookup.city" field="city" :options="$suggestions['city']" hint required />
            </x-ui.field>
            <x-ui.field :label="__('search.hotels.fields.country')" for="criteria.country">
                <x-ui.combobox name="criteria.country" model="lookup.country" field="country" :options="$suggestions['country']" required />
            </x-ui.field>
            <x-ui.field :label="__('search.hotels.fields.ages')" for="criteria.ages" :hint="__('search.ages_hint')">
                <x-ui.input name="criteria.ages" wire:model="criteria.ages" hint required />
            </x-ui.field>
            <x-ui.field :label="__('search.hotels.fields.check_in')" for="criteria.check_in">
                <x-ui.input name="criteria.check_in" type="date" wire:model="criteria.check_in" required />
            </x-ui.field>
            <x-ui.field :label="__('search.hotels.fields.check_out')" for="criteria.check_out">
                <x-ui.input name="criteria.check_out" type="date" wire:model="criteria.check_out" required />
            </x-ui.field>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="search">{{ __('search.search') }}</x-ui.button>
        </form>
    </x-ui.card>

    <div wire:loading.delay wire:target="search" role="status"><x-ui.skeleton :lines="5" /><span class="sr-only">{{ __('search.searching') }}</span></div>

    @if ($result)
        <div wire:loading.remove wire:target="search" class="flex flex-col gap-md">
            @if (session('status'))<x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
            @if ($drafts === [])
                <p class="text-caption text-text-subtle">{{ __('search.no_drafts') }}</p>
            @else
                <x-ui.field :label="__('search.target_quote')" for="targetQuote">
                    <x-ui.select name="targetQuote" wire:model="targetQuote" :options="$drafts" :placeholder="__('quotes.choose')" />
                </x-ui.field>
            @endif
            @error('targetQuote')<x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            @if ($result->unavailableProviders !== [])
                <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Warning">{{ __('search.partial', ['providers' => implode(', ', $result->unavailableProviders)]) }}</x-ui.alert>
            @endif
            @if ($result->offers === [])
                <x-ui.empty-state :title="__('search.empty_title')" :description="__('search.empty_description')" />
            @else
                <ul class="flex flex-col gap-sm">
                    @foreach ($result->offers as $offer)
                        @php($sale = $prices[$offer->providerKey . $offer->offerId] ?? null)
                        <li wire:key="hotel-{{ $offer->providerKey }}-{{ $offer->offerId }}">
                            <x-ui.card>
                                <div class="flex flex-wrap items-start justify-between gap-md">
                                    <div class="flex flex-col gap-xs">
                                        <p class="font-medium">{{ $offer->hotelName }} @if ($offer->stars) <span class="text-caption text-text-subtle">· {{ trans_choice('search.hotels.stars', $offer->stars, ['count' => $offer->stars]) }}</span> @endif</p>
                                        <p>{{ $offer->roomName }} · {{ $offer->boardType->label() }}</p>
                                        <p class="text-caption text-text-subtle">
                                            @if ($offer->freeCancellationUntil)
                                                {{ __('search.hotels.free_cancellation', ['date' => $offer->freeCancellationUntil->locale(app()->getLocale())->isoFormat('ll')]) }}
                                            @else
                                                {{ __('search.non_refundable') }}
                                            @endif
                                            · {{ __('search.provider', ['provider' => $offer->providerKey]) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        @if ($sale)
                                            <p class="text-heading-3 font-semibold">{{ $presenter->format($sale) }}</p>
                                        @else
                                            <p class="text-caption text-warning">{{ __('search.no_rate') }}</p>
                                        @endif
                                        <p class="text-caption text-text-subtle">{{ trans_choice('search.hotels.nights', $nights, ['count' => $nights]) }} · {{ __('search.net', ['amount' => $presenter->format($offer->totalNet)]) }}</p>
                                        @if ($drafts !== [])
                                            <x-ui.button variant="secondary" wire:click="addToQuote('{{ $offer->providerKey . $offer->offerId }}')" wire:loading.attr="disabled">{{ __('search.add_to_quote') }}</x-ui.button>
                                        @endif
                                    </div>
                                </div>
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
