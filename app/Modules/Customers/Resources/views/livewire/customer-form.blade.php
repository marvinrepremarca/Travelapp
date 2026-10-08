<form wire:submit="save" class="flex max-w-page flex-col gap-lg" novalidate>
    <x-ui.card :title="__('customers.identity_section')">
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('customers.fields.type')" for="type">
                <x-ui.select name="type" wire:model.live="type"
                    :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            </x-ui.field>
            <div></div>
            @if ($isPerson)
                <x-ui.field :label="__('customers.fields.first_name')" for="first_name">
                    <x-ui.input name="first_name" wire:model="first_name" autocomplete="given-name" required />
                </x-ui.field>
                <x-ui.field :label="__('customers.fields.last_name')" for="last_name">
                    <x-ui.input name="last_name" wire:model="last_name" autocomplete="family-name" required />
                </x-ui.field>
            @else
                <x-ui.field :label="__('customers.fields.legal_name')" for="legal_name" class="md:col-span-2">
                    <x-ui.input name="legal_name" wire:model="legal_name" autocomplete="organization" required />
                </x-ui.field>
            @endif
            <x-ui.field :label="__('customers.fields.document_type')" for="document_type">
                <x-ui.select name="document_type" wire:model="document_type"
                    :options="collect($documentTypes)->mapWithKeys(fn ($document) => [$document->value => $document->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.document_number')" for="document_number"
                :hint="$isEditing ? __('customers.document_edit_hint') : __('customers.document_hint')">
                <x-ui.input name="document_number" wire:model="document_number" autocomplete="off" hint :required="! $isEditing" />
            </x-ui.field>
            @if ($isPerson)
                <x-ui.field :label="__('customers.fields.birth_date')" for="birth_date"
                    :hint="$isEditing ? __('customers.birth_date_edit_hint') : null">
                    <x-ui.input name="birth_date" type="date" wire:model="birth_date" :hint="$isEditing" />
                </x-ui.field>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('customers.contact_section')">
        @if ($possibleDuplicate)
            <x-ui.alert :tone="\App\Modules\Shared\Enums\Tone::Warning" class="mb-md">{{ __('customers.possible_duplicate') }}</x-ui.alert>
        @endif
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('customers.fields.email')" for="email">
                <x-ui.input name="email" type="email" wire:model.live.debounce.500ms="email" autocomplete="off" />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.phone')" for="phone">
                <x-ui.input name="phone" type="tel" wire:model.live.debounce.500ms="phone" autocomplete="off" />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.city')" for="city">
                <x-ui.input name="city" wire:model="city" />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.country')" for="country" :hint="__('customers.country_hint')">
                <x-ui.input name="country" wire:model="country" maxlength="2" hint />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.notes')" for="notes" class="md:col-span-2" :hint="__('customers.notes_hint')">
                <x-ui.input name="notes" wire:model="notes" hint />
            </x-ui.field>
        </div>
    </x-ui.card>

    @unless ($isEditing)
        <x-ui.card :title="__('customers.consent_section')">
            <div class="flex flex-col gap-md">
                <p class="text-text-subtle">{{ __('customers.consent_help', ['version' => $policyVersion]) }}</p>
                <x-ui.field :label="__('customers.fields.consent_channel')" for="consent_channel">
                    <x-ui.select name="consent_channel" wire:model="consent_channel"
                        :options="collect($channels)->mapWithKeys(fn ($channel) => [$channel->value => $channel->label()])->all()" />
                </x-ui.field>
                <label class="flex items-start gap-sm">
                    <input type="checkbox" wire:model="consent_data_processing" class="mt-xs rounded-control border-border"
                        @error('consent_data_processing') aria-invalid="true" aria-describedby="consent_data_processing-error" @enderror>
                    <span>{{ __('customers.consent_data_processing') }}</span>
                </label>
                @error('consent_data_processing')
                    <p id="consent_data_processing-error" class="text-caption text-danger" role="alert">{{ $message }}</p>
                @enderror
                <label class="flex items-start gap-sm">
                    <input type="checkbox" wire:model="consent_marketing" class="mt-xs rounded-control border-border">
                    <span>{{ __('customers.consent_marketing') }}</span>
                </label>
            </div>
        </x-ui.card>
    @endunless

    <div class="flex justify-end gap-sm">
        <x-ui.link-button :href="route('customers.index')" variant="secondary">{{ __('shared.cancel') }}</x-ui.link-button>
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ __('shared.save') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
