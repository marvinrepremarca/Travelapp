@php
    use App\Modules\Payments\Enums\PaymentMethod;
    use App\Modules\Payments\Enums\PaymentStatus;
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    <x-ui.card>
        <div class="flex flex-col gap-sm">
            <p class="text-text-subtle">{{ $account->title }} · {{ $account->customerName }} · <a href="{{ route('bookings.show', $account->ulid) }}" wire:navigate class="text-brand underline">{{ __('payments.view_booking') }}</a></p>
            <dl class="grid gap-md md:grid-cols-4">
                <div><dt class="text-caption text-text-subtle">{{ __('payments.summary.total') }}</dt><dd class="font-medium">{{ $presenter->format($summary->total) }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('payments.summary.paid') }}</dt><dd class="font-medium text-success">{{ $presenter->format($summary->paid) }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('payments.summary.pending') }}</dt><dd class="font-medium">{{ $presenter->format($summary->pending) }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('payments.summary.balance') }}</dt><dd class="text-heading-3 font-semibold">{{ $presenter->format($summary->balance) }}</dd></div>
            </dl>
            @if ($summary->dueDate)
                <p @class(['text-danger font-medium' => $summary->isOverdue, 'text-text-subtle' => ! $summary->isOverdue])>
                    {{ $summary->isOverdue ? __('payments.summary.overdue', ['date' => $summary->dueDate->locale(app()->getLocale())->isoFormat('ll')]) : __('payments.summary.due', ['date' => $summary->dueDate->locale(app()->getLocale())->isoFormat('ll')]) }}
                </p>
            @endif
        </div>
    </x-ui.card>

    <x-ui.card :title="__('payments.register.title')">
        @if ($summary->collectable()->isPositive())
            <form wire:submit="register" class="grid gap-md md:grid-cols-4 md:items-end" novalidate>
                <x-ui.field :label="__('payments.fields.method')" for="form.method">
                    <x-ui.select name="form.method" wire:model.live="form.method" :options="collect($methods)->mapWithKeys(fn ($method) => [$method->value => $method->label()])->all()" />
                </x-ui.field>
                <x-ui.field :label="__('payments.fields.amount') . ' (' . $summary->total->getCurrency()->getCurrencyCode() . ')'" for="form.amount" :hint="__('payments.register.max', ['amount' => $presenter->format($summary->collectable())])">
                    <x-ui.input name="form.amount" type="number" min="0" step="any" wire:model="form.amount" hint required />
                </x-ui.field>
                @if ($form['method'] !== PaymentMethod::OnlineLink->value)
                    <x-ui.field :label="__('payments.fields.reference')" for="form.reference">
                        <x-ui.input name="form.reference" wire:model="form.reference" />
                    </x-ui.field>
                @endif
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="register">
                    {{ $form['method'] === PaymentMethod::OnlineLink->value ? __('payments.register.create_link') : __('payments.register.save') }}
                </x-ui.button>
            </form>
        @else
            <p class="text-success">{{ __('payments.register.nothing_to_collect') }}</p>
        @endif
    </x-ui.card>

    <x-ui.card :title="__('payments.list.title')">
        @error('payments')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
        @if ($payments->isEmpty())
            <x-ui.empty-state :title="__('payments.list.empty')" />
        @else
            <ul class="flex flex-col divide-y divide-border">
                @foreach ($payments as $payment)
                    <li class="flex flex-wrap items-start justify-between gap-sm py-sm" wire:key="payment-{{ $payment->ulid }}">
                        <div>
                            <p class="font-medium">{{ $presenter->format($payment->amount()) }} · {{ $payment->method->label() }}</p>
                            <p class="text-caption text-text-subtle">
                                {{ $payment->created_at?->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll') }}
                                @if ($payment->reference) · {{ __('payments.list.reference', ['reference' => $payment->reference]) }} @endif
                            </p>
                            @if ($payment->method === PaymentMethod::OnlineLink && $payment->status === PaymentStatus::Pending && $payment->link_url)
                                <div class="mt-xs flex flex-col gap-xs md:flex-row" x-data="{ copied: false }">
                                    <x-ui.input name="link-{{ $payment->ulid }}" :value="$payment->link_url" readonly x-ref="link" aria-label="{{ __('payments.list.link') }}" />
                                    <x-ui.button type="button" variant="secondary" x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true">{{ __('quotes.public.copy_link') }}</x-ui.button>
                                    <span class="text-caption text-success" x-show="copied" x-cloak role="status">{{ __('quotes.public.copied') }}</span>
                                </div>
                                <p class="text-caption text-text-subtle">{{ __('payments.list.link_expires', ['date' => $payment->link_expires_at?->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-sm">
                            <x-ui.badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-ui.badge>
                            @if ($canValidate && $payment->method === PaymentMethod::BankTransfer && $payment->status === PaymentStatus::Pending)
                                <x-ui.button variant="secondary" wire:click="validateTransfer('{{ $payment->ulid }}', true)" wire:loading.attr="disabled">{{ __('payments.list.approve') }}</x-ui.button>
                                <x-ui.button variant="ghost" wire:click="validateTransfer('{{ $payment->ulid }}', false)" wire:loading.attr="disabled" wire:confirm="{{ __('payments.list.confirm_reject') }}">{{ __('payments.list.reject') }}</x-ui.button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</div>
