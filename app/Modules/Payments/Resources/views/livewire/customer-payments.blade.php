@php
    use App\Modules\Payments\Enums\PaymentMethod;
    use App\Modules\Payments\Enums\PaymentStatus;
    use App\Modules\Shared\Enums\Tone;
    $date = fn ($instant) => $instant->timezone(config('travel.agency.timezone'))->locale(app()->getLocale())->isoFormat('lll');
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <p class="text-text-subtle">{{ __('payments.customer_payments.intro') }}</p>

    <x-ui.card :title="__('payments.customer_payments.register')">
        <form wire:submit="register" class="flex flex-col gap-md" novalidate>
            @if ($selected)
                <p class="font-medium">{{ __('payments.customer_payments.customer_selected', ['name' => $selected->display_name]) }}</p>
            @endif
            <x-ui.field :label="__('payments.customer_payments.customer_search')" for="customerSearch">
                <x-ui.input name="customerSearch" type="search" wire:model.live.debounce.400ms="customerSearch" />
            </x-ui.field>
            @error('customerUlid')<p class="text-caption text-danger" role="alert">{{ $message }}</p>@enderror
            @if ($customerSearch !== '')
                @if ($matches->isEmpty())
                    <p class="text-text-subtle">{{ __('payments.customer_payments.no_matches') }}</p>
                @else
                    <ul class="flex flex-col divide-y divide-border">
                        @foreach ($matches as $match)
                            <li wire:key="customer-{{ $match->ulid }}">
                                <button type="button" class="w-full py-xs text-left text-brand underline" wire:click="chooseCustomer('{{ $match->ulid }}')">{{ $match->display_name }}</button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            <div class="grid gap-md md:grid-cols-4 md:items-end">
                <x-ui.field :label="__('payments.customer_payments.fields.form.method')" for="form.method">
                    <x-ui.select name="form.method" wire:model.live="form.method"
                        :options="collect($methods)->mapWithKeys(fn ($method) => [$method->value => $method->label()])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('payments.customer_payments.amount_in', ['currency' => $currency])" for="form.amount">
                    <x-ui.input name="form.amount" type="number" min="0" step="any" inputmode="decimal" wire:model="form.amount" />
                </x-ui.field>
                <x-ui.field :label="__('payments.customer_payments.fields.form.concept')" for="form.concept">
                    <x-ui.input name="form.concept" wire:model="form.concept" />
                </x-ui.field>
                <x-ui.field :label="__('payments.customer_payments.fields.form.reference')" for="form.reference">
                    <x-ui.input name="form.reference" wire:model="form.reference" />
                </x-ui.field>
            </div>
            <div>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="register">{{ __('payments.customer_payments.register') }}</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    @error('payments')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror

    @if ($payments->isEmpty())
        <x-ui.empty-state :title="__('payments.customer_payments.empty')" />
    @else
        <x-ui.table :caption="__('payments.customer_payments.list')">
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-md py-sm">{{ __('payments.customer_payments.customer') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('payments.customer_payments.concept') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('payments.customer_payments.fields.form.method') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('payments.customer_payments.fields.form.amount') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($payments as $payment)
                <tr wire:key="payment-{{ $payment->ulid }}">
                    <td class="px-md py-sm">{{ $payment->customer?->display_name }}</td>
                    <td class="px-md py-sm">
                        {{ $payment->concept }}
                        <p class="text-caption text-text-subtle">{{ $date($payment->created_at) }}@if ($payment->reference) · {{ $payment->reference }}@endif</p>
                    </td>
                    <td class="px-md py-sm">
                        {{ $payment->method->label() }}
                        <p><x-ui.badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-ui.badge></p>
                        @if ($canValidate && $payment->method === PaymentMethod::BankTransfer && $payment->status === PaymentStatus::Pending)
                            <div class="mt-xs flex gap-xs">
                                <x-ui.button variant="secondary" wire:click="validateTransfer('{{ $payment->ulid }}', true)" wire:loading.attr="disabled">{{ __('payments.list.approve') }}</x-ui.button>
                                <x-ui.button variant="ghost" wire:click="validateTransfer('{{ $payment->ulid }}', false)" wire:loading.attr="disabled" wire:confirm="{{ __('payments.list.confirm_reject') }}">{{ __('payments.list.reject') }}</x-ui.button>
                            </div>
                        @endif
                    </td>
                    <td class="px-md py-sm font-medium">{{ $presenter->format($payment->amount()) }}</td>
                </tr>
            @endforeach
        </x-ui.table>
        <div>{{ $payments->links() }}</div>
    @endif
</div>
