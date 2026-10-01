@php
    use App\Modules\Quotes\Enums\QuoteStatus;
    use App\Modules\Shared\Enums\Tone;
    use Brick\Money\Money;
    $currency = $quote->sale_currency;
    $formatDate = fn ($date) => $date->locale(app()->getLocale())->isoFormat('ll');
    $formatInstant = fn ($instant) => $instant->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll');
@endphp
<div class="flex flex-col gap-lg">
    <x-ui.card>
        <div class="flex flex-col gap-md">
            <div class="flex flex-wrap items-center gap-sm">
                <x-ui.badge :tone="$quote->status->tone()">{{ $quote->status->label() }}</x-ui.badge>
                <span class="text-caption text-text-subtle">{{ $quote->customer->display_name }} · {{ $quote->sales_channel->label() }} · {{ $currency }}</span>
            </div>
            @if ($quote->status === QuoteStatus::Sent && $quote->valid_until)
                <p>{{ __('quotes.validity', ['date' => $formatInstant($quote->valid_until)]) }}</p>
            @endif
            @if ($quote->status === QuoteStatus::Expired)
                <x-ui.alert :tone="Tone::Warning">{{ __('quotes.expired_notice') }}</x-ui.alert>
            @endif
            @if ($quote->status === QuoteStatus::Accepted && Route::has('bookings.from-quote'))
                <div class="flex justify-end">
                    <x-ui.link-button :href="route('bookings.from-quote', $quote->ulid)">{{ __('bookings.convert.from_quote_link') }}</x-ui.link-button>
                </div>
            @endif
            @if ($quote->status === QuoteStatus::Accepted)
                <x-ui.alert :tone="Tone::Success">
                    {{ __('quotes.accepted_notice', ['label' => $quote->options->firstWhere('id', $quote->accepted_option_id)?->label, 'version' => $quote->accepted_version, 'channel' => $quote->acceptance_channel?->label()]) }}
                </x-ui.alert>
            @endif
            @error('quote')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            @if ($customerLink)
                <div class="flex flex-col gap-xs" x-data="{ copied: false }">
                    <label for="customer-link" class="text-caption text-text-subtle">{{ __('quotes.public.link_label') }}</label>
                    <div class="flex flex-col gap-sm md:flex-row">
                        <x-ui.input name="customer-link" :value="$customerLink" readonly x-ref="link" />
                        <x-ui.button type="button" variant="secondary" x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true">{{ __('quotes.public.copy_link') }}</x-ui.button>
                    </div>
                    <p class="text-caption text-success" x-show="copied" x-cloak role="status">{{ __('quotes.public.copied') }}</p>
                </div>
            @endif

            <div class="flex flex-wrap justify-end gap-sm">
                @if ($quote->status === QuoteStatus::Draft)
                    <x-ui.button wire:click="send" wire:loading.attr="disabled" wire:confirm="{{ __('quotes.actions.confirm_send') }}">{{ __('quotes.actions.send') }}</x-ui.button>
                @endif
                @if (in_array($quote->status, [QuoteStatus::Sent, QuoteStatus::Expired], true))
                    <x-ui.button variant="secondary" wire:click="revise" wire:loading.attr="disabled" wire:confirm="{{ __('quotes.actions.confirm_revise') }}">{{ __('quotes.actions.revise') }}</x-ui.button>
                @endif
                @if ($quote->status->canTransitionTo(QuoteStatus::Cancelled))
                    <x-ui.button variant="ghost" wire:click="cancel" wire:loading.attr="disabled" wire:confirm="{{ __('quotes.actions.confirm_cancel') }}">{{ __('quotes.actions.cancel') }}</x-ui.button>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('quotes.options.title')">
        <div role="tablist" aria-label="{{ __('quotes.options.title') }}" class="mb-md flex flex-wrap gap-sm">
            @foreach ($quote->options as $option)
                <x-ui.button role="tab" :variant="$activeOption?->is($option) ? 'primary' : 'secondary'" aria-selected="{{ $activeOption?->is($option) ? 'true' : 'false' }}"
                    wire:click="$set('optionUlid', '{{ $option->ulid }}')" wire:key="tab-{{ $option->ulid }}">
                    {{ __('quotes.options.label', ['label' => $option->label]) }} · {{ $presenter->format($option->saleTotal($currency)) }}
                </x-ui.button>
            @endforeach
        </div>
        @error('option')<p class="mb-md text-caption text-danger" role="alert">{{ $message }}</p>@enderror

        @if ($activeOption)
            <div class="flex flex-col gap-md" role="tabpanel">
                <div class="flex flex-wrap items-center justify-between gap-sm">
                    <h3 class="text-heading-3">{{ $activeOption->title }}</h3>
                    @if ($editable && $quote->options->count() > 1)
                        <x-ui.button variant="ghost" wire:click="removeOption('{{ $activeOption->ulid }}')" wire:confirm="{{ __('quotes.options.confirm_remove') }}">{{ __('quotes.options.remove') }}</x-ui.button>
                    @endif
                </div>

                @if ($activeOption->items->isEmpty())
                    <x-ui.empty-state :title="__('quotes.options.empty')" />
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($activeOption->items as $line)
                            @php($rate = $line->price_breakdown['exchange_rate'] ?? null)
                            <li class="flex flex-wrap items-start justify-between gap-sm py-sm" wire:key="item-{{ $line->ulid }}">
                                <div>
                                    <p class="font-medium">{{ $line->description }}</p>
                                    <p class="text-caption text-text-subtle">
                                        {{ $line->product_type->label() }} · {{ __('quotes.items.service', ['date' => $formatDate($line->service_date), 'nights' => $line->nights, 'passengers' => count($line->passenger_ages)]) }}
                                    </p>
                                    @if ($canSeeMargin)
                                        <p class="text-caption text-text-subtle">
                                            {{ __('quotes.items.net', ['amount' => $presenter->format($line->netAmount())]) }}
                                            @if ($rate) · {{ __('quotes.items.rate', ['rate' => $rate['rate'], 'date' => $rate['rate_date']]) }} @endif
                                            · {{ __('quotes.options.margin') }} {{ $presenter->format($line->marginAmount()) }}
                                        </p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-sm">
                                    <span class="font-medium">{{ $presenter->format($line->saleAmount()) }}</span>
                                    @if ($editable)
                                        <x-ui.button variant="ghost" wire:click="removeItem('{{ $line->ulid }}')" wire:loading.attr="disabled">
                                            {{ __('quotes.items.remove') }}<span class="sr-only"> {{ $line->description }}</span>
                                        </x-ui.button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-right font-medium">
                        {{ __('quotes.options.total') }}: {{ $presenter->format($activeOption->saleTotal($currency)) }}
                        @if ($canSeeMargin) · {{ __('quotes.options.margin') }}: {{ $presenter->format($activeOption->marginTotal($currency)) }} @endif
                    </p>
                @endif

                @if ($itinerary !== [])
                    <section aria-labelledby="itinerary-title" class="border-t border-border pt-md">
                        <h4 id="itinerary-title" class="mb-sm font-medium">{{ __('quotes.itinerary.title') }}</h4>
                        @include('quotes::partials.itinerary', ['days' => $itinerary])
                    </section>
                @endif

                @if ($editable)
                    @include('quotes::partials.item-form')
                @endif
            </div>
        @endif

        @if ($editable)
            <form wire:submit="addOption" class="mt-lg flex flex-col gap-sm md:flex-row md:items-end" novalidate>
                <x-ui.field :label="__('quotes.fields.option_title')" for="newOptionTitle"><x-ui.input name="newOptionTitle" wire:model="newOptionTitle" /></x-ui.field>
                <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="addOption">{{ __('quotes.options.add') }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>

    @if ($quote->status === QuoteStatus::Sent)
        <x-ui.card :title="__('quotes.actions.accept')">
            <form wire:submit="accept" class="grid gap-md md:grid-cols-3 md:items-end" novalidate>
                <x-ui.field :label="__('quotes.fields.accepted_option')" for="acceptance.option">
                    <x-ui.select name="acceptance.option" wire:model="acceptance.option" :placeholder="__('quotes.choose')"
                        :options="$quote->options->mapWithKeys(fn ($option) => [$option->ulid => __('quotes.options.label', ['label' => $option->label]) . ' · ' . $option->title])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('quotes.fields.acceptance_note')" for="acceptance.note"><x-ui.input name="acceptance.note" wire:model="acceptance.note" /></x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="accept">{{ __('quotes.actions.accept') }}</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :title="__('quotes.versions.title')">
        @forelse ($versions as $version)
            <p wire:key="version-{{ $version->id }}">{{ __('quotes.versions.row', ['version' => $version->version, 'sent' => $formatInstant($version->sent_at), 'until' => $formatInstant($version->valid_until)]) }}</p>
        @empty
            <p class="text-text-subtle">{{ __('quotes.versions.empty') }}</p>
        @endforelse
    </x-ui.card>
</div>
