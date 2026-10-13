@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="grid gap-lg lg:grid-cols-3">
    <div class="flex flex-col gap-lg lg:col-span-2">
        <x-ui.card>
            <div class="flex flex-col gap-md">
                <div class="flex flex-wrap items-center gap-sm">
                    <x-ui.badge :tone="$standing->tone()">{{ $standing->label() }}</x-ui.badge>
                    @unless ($standing->isBookable())
                        <span class="text-caption text-danger">{{ __('suppliers.not_bookable') }}</span>
                    @endunless
                </div>
                <dl class="grid gap-md md:grid-cols-2">
                    <div><dt class="text-caption text-text-subtle">{{ __('suppliers.fields.legal_name') }}</dt><dd>{{ $supplier->legal_name }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('suppliers.fields.tax_id') }}</dt><dd>{{ $supplier->tax_id }} · {{ $supplier->country }}</dd></div>
                    <div>
                        <dt class="text-caption text-text-subtle">{{ __('suppliers.fields.rnt_number') }}</dt>
                        <dd>{{ $supplier->rnt_number ?? '—' }} @if ($supplier->rnt_expires_on) ({{ __('suppliers.expires', ['date' => $supplier->rnt_expires_on->locale(app()->getLocale())->isoFormat('ll')]) }}) @endif</dd>
                    </div>
                    <div><dt class="text-caption text-text-subtle">{{ __('suppliers.fields.payment_terms') }}</dt>
                        <dd>{{ __('suppliers.terms_summary', ['terms' => $supplier->payment_terms->label(), 'days' => $supplier->payment_days, 'currency' => $supplier->payment_currency]) }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('suppliers.fields.email') }}</dt><dd>{{ $supplier->email ?? '—' }}</dd></div>
                    <div><dt class="text-caption text-text-subtle">{{ __('suppliers.fields.phone') }}</dt><dd>{{ $supplier->phone ?? '—' }}</dd></div>
                </dl>
                @if ($canManage)
                    <div class="flex justify-end gap-sm">
                        <x-ui.button variant="ghost" wire:click="toggleActive" wire:loading.attr="disabled"
                            wire:confirm="{{ $supplier->is_active ? __('suppliers.confirm_deactivate') : __('suppliers.confirm_activate') }}">
                            {{ $supplier->is_active ? __('suppliers.deactivate') : __('suppliers.activate') }}
                        </x-ui.button>
                        <x-ui.link-button :href="route('suppliers.edit', $supplier)" variant="secondary">{{ __('shared.edit') }}</x-ui.link-button>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card :title="__('suppliers.commissions')">
            @error('commission.valid_from') <x-ui.alert :tone="Tone::Danger" class="mb-sm">{{ $message }}</x-ui.alert> @enderror
            @if ($supplier->commissions->isEmpty())
                <x-ui.empty-state :title="__('suppliers.no_commissions')" />
            @else
                <x-ui.table :caption="__('suppliers.commissions')">
                    <x-slot:head>
                        <tr>
                            <th scope="col" class="px-md py-sm">{{ __('suppliers.commission_fields.product_type') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('suppliers.commission_fields.rate') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('suppliers.commission_fields.base') }}</th>
                            <th scope="col" class="px-md py-sm">{{ __('suppliers.validity') }}</th>
                            @if ($canManage)<th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>@endif
                        </tr>
                    </x-slot:head>
                    @foreach ($supplier->commissions as $commission)
                        <tr wire:key="commission-{{ $commission->id }}">
                            <td class="px-md py-sm">{{ $commission->product_type->label() }}</td>
                            <td class="px-md py-sm">{{ __('suppliers.percent', ['value' => $commission->rate()->toPercentString()]) }}</td>
                            <td class="px-md py-sm">{{ $commission->base->label() }}</td>
                            <td class="px-md py-sm">
                                {{ $commission->valid_from->toDateString() }} – {{ $commission->valid_until?->toDateString() ?? __('suppliers.open_ended') }}
                                @if ($commission->appliesOn($today)) <x-ui.badge :tone="Tone::Success">{{ __('suppliers.current') }}</x-ui.badge> @endif
                            </td>
                            @if ($canManage)
                                <td class="px-md py-sm">
                                    @if ($commission->valid_until === null)
                                        <x-ui.button variant="ghost" wire:click="endCommission({{ $commission->id }})" wire:loading.attr="disabled"
                                            wire:confirm="{{ __('suppliers.confirm_end_commission') }}">{{ __('suppliers.end_commission') }}</x-ui.button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </x-ui.table>
            @endif

            @if ($canManage)
                <form wire:submit="addCommission" class="mt-md grid gap-md md:grid-cols-5 md:items-end" novalidate>
                    <x-ui.field :label="__('suppliers.commission_fields.product_type')" for="commission.product_type">
                        <x-ui.select name="commission.product_type" wire:model="commission.product_type" :placeholder="__('suppliers.choose')"
                            :options="collect($productTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('suppliers.commission_fields.rate')" for="commission.rate">
                        <x-ui.input name="commission.rate" inputmode="decimal" wire:model="commission.rate" />
                    </x-ui.field>
                    <x-ui.field :label="__('suppliers.commission_fields.base')" for="commission.base">
                        <x-ui.select name="commission.base" wire:model="commission.base"
                            :options="collect($bases)->mapWithKeys(fn ($base) => [$base->value => $base->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('suppliers.commission_fields.valid_from')" for="commission.valid_from">
                        <x-ui.input name="commission.valid_from" type="date" wire:model="commission.valid_from" />
                    </x-ui.field>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addCommission">{{ __('suppliers.add_commission') }}</x-ui.button>
                </form>
            @endif
        </x-ui.card>
    </div>

    <div class="flex flex-col gap-lg">
        <x-ui.card :title="__('suppliers.contacts')">
            <ul class="flex flex-col gap-sm">
                @forelse ($supplier->contacts as $contact)
                    <li wire:key="contact-{{ $contact->id }}" class="flex flex-col gap-xs">
                        @if ($editingContactId === $contact->id)
                            <form wire:submit="updateContact" class="flex flex-col gap-sm" novalidate>
                                <x-ui.field :label="__('suppliers.contact_fields.name')" for="contactEdit.name"><x-ui.input name="contactEdit.name" wire:model="contactEdit.name" required /></x-ui.field>
                                <x-ui.field :label="__('suppliers.contact_fields.position')" for="contactEdit.position"><x-ui.input name="contactEdit.position" wire:model="contactEdit.position" /></x-ui.field>
                                <x-ui.field :label="__('suppliers.contact_fields.email')" for="contactEdit.email"><x-ui.input name="contactEdit.email" type="email" wire:model="contactEdit.email" /></x-ui.field>
                                <x-ui.field :label="__('suppliers.contact_fields.phone')" for="contactEdit.phone"><x-ui.input name="contactEdit.phone" type="tel" wire:model="contactEdit.phone" /></x-ui.field>
                                <div class="flex justify-end gap-sm">
                                    <x-ui.button variant="ghost" wire:click="cancelEditContact">{{ __('shared.cancel') }}</x-ui.button>
                                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateContact">{{ __('shared.save') }}</x-ui.button>
                                </div>
                            </form>
                        @else
                            <p class="font-medium">
                                {{ $contact->name }} @if ($contact->position) · {{ $contact->position }} @endif
                                @unless ($contact->is_active) <x-ui.badge :tone="Tone::Warning">{{ __('suppliers.record_inactive') }}</x-ui.badge> @endunless
                            </p>
                            <p class="text-caption text-text-subtle">{{ $contact->email }} {{ $contact->phone }}</p>
                            @if ($canManageRecords)
                                <div class="flex flex-wrap gap-sm">
                                    <x-ui.button variant="ghost" wire:click="startEditContact({{ $contact->id }})" wire:loading.attr="disabled">
                                        {{ __('shared.edit') }}<span class="sr-only"> {{ $contact->name }}</span>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" wire:click="toggleContact({{ $contact->id }})" wire:loading.attr="disabled"
                                        wire:confirm="{{ $contact->is_active ? __('suppliers.confirm_deactivate_contact') : __('suppliers.confirm_reactivate_record') }}">
                                        {{ $contact->is_active ? __('suppliers.deactivate_record') : __('suppliers.reactivate_record') }}<span class="sr-only"> {{ $contact->name }}</span>
                                    </x-ui.button>
                                </div>
                            @endif
                        @endif
                    </li>
                @empty
                    <li class="text-text-subtle">{{ __('suppliers.no_contacts') }}</li>
                @endforelse
            </ul>
            @if ($canManage)
                <form wire:submit="addContact" class="mt-md flex flex-col gap-sm" novalidate>
                    <x-ui.field :label="__('suppliers.contact_fields.name')" for="contact.name"><x-ui.input name="contact.name" wire:model="contact.name" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.contact_fields.position')" for="contact.position"><x-ui.input name="contact.position" wire:model="contact.position" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.contact_fields.email')" for="contact.email"><x-ui.input name="contact.email" type="email" wire:model="contact.email" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.contact_fields.phone')" for="contact.phone"><x-ui.input name="contact.phone" type="tel" wire:model="contact.phone" /></x-ui.field>
                    <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="addContact">{{ __('suppliers.add_contact') }}</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        @if ($canSeeBank || $canManageRecords)
            <x-ui.card :title="__('suppliers.bank_accounts')">
                <ul class="flex flex-col gap-sm">
                    @forelse ($supplier->bankAccounts as $account)
                        <li wire:key="account-{{ $account->ulid }}" class="flex flex-col gap-xs">
                            @if ($editingAccountUlid === $account->ulid)
                                <form wire:submit="updateAccount" class="flex flex-col gap-sm" novalidate>
                                    <x-ui.field :label="__('suppliers.account_fields.bank_name')" for="accountEdit.bank_name"><x-ui.input name="accountEdit.bank_name" wire:model="accountEdit.bank_name" required /></x-ui.field>
                                    <x-ui.field :label="__('suppliers.account_fields.account_type')" for="accountEdit.account_type">
                                        <x-ui.select name="accountEdit.account_type" wire:model="accountEdit.account_type"
                                            :options="collect($accountTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" required />
                                    </x-ui.field>
                                    <x-ui.field :label="__('suppliers.account_fields.number')" for="accountEdit.number" :hint="__('suppliers.account_number_keep')">
                                        <x-ui.input name="accountEdit.number" wire:model="accountEdit.number" autocomplete="off" hint />
                                    </x-ui.field>
                                    <x-ui.field :label="__('suppliers.account_fields.holder_name')" for="accountEdit.holder_name"><x-ui.input name="accountEdit.holder_name" wire:model="accountEdit.holder_name" required /></x-ui.field>
                                    <x-ui.field :label="__('suppliers.account_fields.currency')" for="accountEdit.currency"><x-ui.input name="accountEdit.currency" wire:model="accountEdit.currency" maxlength="3" required /></x-ui.field>
                                    <div class="flex justify-end gap-sm">
                                        <x-ui.button variant="ghost" wire:click="cancelEditAccount">{{ __('shared.cancel') }}</x-ui.button>
                                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateAccount">{{ __('shared.save') }}</x-ui.button>
                                    </div>
                                </form>
                            @else
                                <p class="font-medium">
                                    {{ $account->bank_name }} · {{ $account->account_type->label() }} · {{ $account->currency }}
                                    @unless ($account->is_active) <x-ui.badge :tone="Tone::Warning">{{ __('suppliers.record_inactive') }}</x-ui.badge> @endunless
                                </p>
                                <p class="text-caption text-text-subtle">{{ $revealed[$account->ulid] ?? $account->maskedNumber() }} · {{ $account->holder_name }}</p>
                                <div class="flex flex-wrap gap-sm">
                                    @if ($canSeeBank)
                                        <x-ui.button variant="ghost" wire:click="revealAccount('{{ $account->ulid }}')" wire:loading.attr="disabled">
                                            {{ __('suppliers.reveal_account') }}<span class="sr-only"> {{ $account->bank_name }}</span>
                                        </x-ui.button>
                                    @endif
                                    @if ($canManageRecords)
                                        <x-ui.button variant="ghost" wire:click="startEditAccount('{{ $account->ulid }}')" wire:loading.attr="disabled">
                                            {{ __('shared.edit') }}<span class="sr-only"> {{ $account->bank_name }}</span>
                                        </x-ui.button>
                                        <x-ui.button variant="ghost" wire:click="toggleAccount('{{ $account->ulid }}')" wire:loading.attr="disabled"
                                            wire:confirm="{{ $account->is_active ? __('suppliers.confirm_deactivate_account') : __('suppliers.confirm_reactivate_record') }}">
                                            {{ $account->is_active ? __('suppliers.deactivate_record') : __('suppliers.reactivate_record') }}<span class="sr-only"> {{ $account->bank_name }}</span>
                                        </x-ui.button>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="text-text-subtle">{{ __('suppliers.no_accounts') }}</li>
                    @endforelse
                </ul>
                @if ($canSeeBank)
                <x-ui.field :label="__('customers.reveal_reason')" for="revealReason" class="mt-md">
                    <x-ui.input name="revealReason" wire:model="revealReason" />
                </x-ui.field>
                <form wire:submit="addBankAccount" class="mt-md flex flex-col gap-sm" novalidate>
                    <x-ui.field :label="__('suppliers.account_fields.bank_name')" for="account.bank_name"><x-ui.input name="account.bank_name" wire:model="account.bank_name" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.account_fields.account_type')" for="account.account_type">
                        <x-ui.select name="account.account_type" wire:model="account.account_type"
                            :options="collect($accountTypes)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
                    </x-ui.field>
                    <x-ui.field :label="__('suppliers.account_fields.number')" for="account.number"><x-ui.input name="account.number" wire:model="account.number" autocomplete="off" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.account_fields.holder_name')" for="account.holder_name"><x-ui.input name="account.holder_name" wire:model="account.holder_name" /></x-ui.field>
                    <x-ui.field :label="__('suppliers.account_fields.currency')" for="account.currency"><x-ui.input name="account.currency" wire:model="account.currency" maxlength="3" /></x-ui.field>
                    <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="addBankAccount">{{ __('suppliers.add_account') }}</x-ui.button>
                </form>
                @endif
            </x-ui.card>
        @endif
    </div>
</div>
