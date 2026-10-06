<form wire:submit="save" class="flex flex-col gap-lg" novalidate>
    <x-ui.card :title="__('finance.bank_accounts.account_section')">
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('finance.bank_accounts.fields.name')" for="name" :hint="__('finance.bank_accounts.name_hint')">
                <x-ui.input name="name" wire:model="name" required hint />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.bank_name')" for="bank_name">
                <x-ui.input name="bank_name" wire:model="bank_name" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.account_last_digits')" for="account_last_digits" :hint="__('finance.bank_accounts.last_digits_hint')">
                <x-ui.input name="account_last_digits" wire:model="account_last_digits" inputmode="numeric" maxlength="4" autocomplete="off" required hint />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.currency')" for="currency" :hint="$currencyLocked ? __('finance.bank_accounts.currency_locked') : __('finance.bank_accounts.currency_hint')">
                <x-ui.input name="currency" wire:model="currency" maxlength="3" :disabled="$currencyLocked" required hint />
            </x-ui.field>
            <label class="flex items-center gap-sm text-body">
                <input type="checkbox" wire:model="is_active" class="rounded-control border-border">
                {{ __('finance.bank_accounts.fields.is_active') }}
            </label>
        </div>
    </x-ui.card>

    <x-ui.card :title="__('finance.bank_accounts.format_section')">
        <p class="mb-md text-text-subtle">{{ __('finance.bank_accounts.format_hint') }}</p>
        <div class="grid gap-md md:grid-cols-2">
            <x-ui.field :label="__('finance.bank_accounts.fields.format_delimiter')" for="format.delimiter">
                <x-ui.select name="format.delimiter" wire:model="format.delimiter" :options="$delimiters" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_decimal_separator')" for="format.decimal_separator">
                <x-ui.select name="format.decimal_separator" wire:model="format.decimal_separator" :options="$decimalSeparators" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_date_format')" for="format.date_format">
                <x-ui.select name="format.date_format" wire:model="format.date_format" :options="$dateFormats" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_header_row')" for="format.header_row" :hint="__('finance.bank_accounts.header_row_hint')">
                <x-ui.input name="format.header_row" type="number" min="1" wire:model="format.header_row" required hint />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_date_column')" for="format.date_column">
                <x-ui.input name="format.date_column" wire:model="format.date_column" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_description_column')" for="format.description_column">
                <x-ui.input name="format.description_column" wire:model="format.description_column" required />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_reference_column')" for="format.reference_column">
                <x-ui.input name="format.reference_column" wire:model="format.reference_column" />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_amount_column')" for="format.amount_column" :hint="__('finance.bank_accounts.amount_hint')">
                <x-ui.input name="format.amount_column" wire:model="format.amount_column" hint />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_debit_column')" for="format.debit_column">
                <x-ui.input name="format.debit_column" wire:model="format.debit_column" />
            </x-ui.field>
            <x-ui.field :label="__('finance.bank_accounts.fields.format_credit_column')" for="format.credit_column">
                <x-ui.input name="format.credit_column" wire:model="format.credit_column" />
            </x-ui.field>
        </div>
    </x-ui.card>

    <div class="flex flex-wrap gap-sm">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('shared.save') }}</x-ui.button>
        <x-ui.link-button variant="secondary" :href="route('finance.bank-accounts')">{{ __('shared.cancel') }}</x-ui.link-button>
    </div>
</form>
