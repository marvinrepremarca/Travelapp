@php
    use App\Modules\Customers\Enums\SensitiveCustomerField;
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="grid gap-lg lg:grid-cols-3">
    <div class="flex flex-col gap-lg lg:col-span-2">
        <x-ui.card :title="__('customers.identity_section')">
            <dl class="grid gap-md md:grid-cols-2">
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.fields.type') }}</dt>
                    <dd>{{ $customer->type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.fields.document_number') }}</dt>
                    <dd>{{ $customer->document_type->label() }}
                        {{ $revealed[SensitiveCustomerField::DocumentNumber->value] ?? $customer->maskedDocument() }}</dd>
                </div>
                @if ($customer->birth_date !== null)
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('customers.fields.birth_date') }}</dt>
                        <dd>{{ $revealed[SensitiveCustomerField::BirthDate->value] ?? __('customers.hidden') }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.fields.email') }}</dt>
                    <dd>{{ $customer->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.fields.phone') }}</dt>
                    <dd>{{ $customer->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.fields.city') }}</dt>
                    <dd>{{ $customer->city ?? '—' }} {{ $customer->country }}</dd>
                </div>
                <div>
                    <dt class="text-caption text-text-subtle">{{ __('customers.owner') }}</dt>
                    <dd>{{ $owner?->name ?? '—' }}</dd>
                </div>
            </dl>
            @if ($customer->notes)
                <p class="mt-md text-text-subtle">{{ $customer->notes }}</p>
            @endif
            <div class="mt-md flex justify-end">
                <x-ui.link-button :href="route('customers.edit', $customer)" variant="secondary">{{ __('shared.edit') }}</x-ui.link-button>
            </div>
        </x-ui.card>

        <x-ui.card :title="__('customers.travelers.title')">
            <div class="flex flex-col gap-md">
                @if ($customer->travelers->isEmpty())
                    <x-ui.empty-state :title="__('customers.travelers.empty_title')" :description="__('customers.travelers.empty_description')" />
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($customer->travelers as $traveler)
                            <li class="flex flex-col gap-xs py-sm md:flex-row md:items-center md:justify-between" wire:key="traveler-{{ $traveler->ulid }}">
                                <div class="flex flex-col gap-xs">
                                    <p class="font-medium">{{ $traveler->fullName() }}</p>
                                    <p class="text-caption text-text-subtle">
                                        {{ $traveler->airlineName() }} · {{ $traveler->gender->label() }} · {{ $traveler->nationality }}
                                        @if ($traveler->passport_number)
                                            · {{ __('customers.travelers.fields.passport_number') }}: {{ $revealed['passport:'.$traveler->ulid] ?? $traveler->maskedPassport() }}
                                        @endif
                                    </p>
                                    <div class="flex flex-wrap gap-xs">
                                        @php($type = $traveler->passengerTypeAt($today))
                                        @if ($type !== \App\Modules\Shared\Enums\PassengerType::Adult)
                                            <x-ui.badge :tone="Tone::Info">{{ $type->label() }}</x-ui.badge>
                                        @endif
                                        @if ($traveler->passport_expires_on === null)
                                            <x-ui.badge :tone="Tone::Neutral">{{ __('customers.travelers.no_passport') }}</x-ui.badge>
                                        @elseif (! $traveler->passportValidFor($today, $passportWarningMonths))
                                            <x-ui.badge :tone="Tone::Warning">{{ __('customers.travelers.passport_expiring', ['date' => $traveler->passport_expires_on->toDateString()]) }}</x-ui.badge>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-sm">
                                    @if ($canReveal && $traveler->passport_number)
                                        <x-ui.button variant="ghost" wire:click="revealPassport('{{ $traveler->ulid }}')" wire:loading.attr="disabled">
                                            {{ __('customers.reveal', ['field' => __('customers.travelers.fields.passport_number')]) }}<span class="sr-only"> {{ $traveler->fullName() }}</span>
                                        </x-ui.button>
                                    @endif
                                    <a href="{{ route('customers.travelers.edit', [$customer, $traveler]) }}" wire:navigate class="text-brand underline">
                                        {{ __('shared.edit') }}<span class="sr-only"> {{ $traveler->fullName() }}</span>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="flex justify-end">
                    <x-ui.link-button :href="route('customers.travelers.create', $customer)" variant="secondary">{{ __('customers.travelers.create') }}</x-ui.link-button>
                </div>
            </div>
        </x-ui.card>

        @if ($canReveal)
            <x-ui.card :title="__('customers.reveal_title')">
                <div class="flex flex-col gap-md">
                    <p class="text-text-subtle">{{ __('customers.reveal_help') }}</p>
                    <x-ui.field :label="__('customers.reveal_reason')" for="revealReason">
                        <x-ui.input name="revealReason" wire:model="revealReason" required />
                    </x-ui.field>
                    <div class="flex flex-wrap gap-sm">
                        @foreach ($fields as $field)
                            <x-ui.button variant="secondary" wire:click="reveal('{{ $field->value }}')" wire:loading.attr="disabled" wire:key="reveal-{{ $field->value }}">
                                {{ __('customers.reveal', ['field' => $field->label()]) }}
                            </x-ui.button>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @endif
    </div>

    <div class="flex flex-col gap-lg">
        <x-ui.card :title="__('customers.consent_section')">
            <div class="flex flex-col gap-md">
                <p>
                    {{ __('customers.consent_purpose.marketing') }}:
                    <x-ui.badge :tone="$marketing ? Tone::Success : Tone::Neutral">{{ $marketing ? __('customers.granted') : __('customers.not_granted') }}</x-ui.badge>
                </p>
                <x-ui.field :label="__('customers.fields.consent_channel')" for="consentChannel">
                    <x-ui.select name="consentChannel" wire:model="consentChannel"
                        :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
                </x-ui.field>
                <x-ui.button variant="secondary" wire:click="toggleMarketing" wire:loading.attr="disabled">
                    {{ $marketing ? __('customers.revoke_marketing') : __('customers.grant_marketing') }}
                </x-ui.button>
                <ul class="flex flex-col gap-xs text-caption text-text-subtle">
                    @foreach ($customer->consents as $consent)
                        <li wire:key="consent-{{ $consent->id }}">
                            {{ $consent->recorded_at->locale(app()->getLocale())->isoFormat('lll') }} ·
                            {{ $consent->purpose->label() }} ·
                            {{ $consent->granted ? __('customers.granted') : __('customers.not_granted') }} ·
                            {{ $consent->channel->label() }} · v{{ $consent->policy_version }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-ui.card>

        @if ($canReassign)
            <x-ui.card :title="__('customers.reassign_title')">
                <form wire:submit="reassign" class="flex flex-col gap-md" novalidate>
                    <x-ui.field :label="__('customers.owner')" for="newOwnerId">
                        <x-ui.select name="newOwnerId" wire:model="newOwnerId" :options="$assignableOwners" :placeholder="__('customers.choose_owner')" />
                    </x-ui.field>
                    <x-ui.button type="submit" wire:loading.attr="disabled">{{ __('customers.reassign') }}</x-ui.button>
                </form>
            </x-ui.card>
        @endif
    </div>
</div>
