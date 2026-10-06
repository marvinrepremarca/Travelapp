@php
    use App\Modules\Finance\Enums\CashMovementType;
    use App\Modules\Shared\Enums\Tone;
    $at = fn ($instant) => $instant?->timezone($timezone)->locale(app()->getLocale())->isoFormat('lll');
@endphp
<div class="flex flex-col gap-lg">
    @if ($branches !== [])
        <x-ui.field :label="__('finance.cash.branch')" for="branch">
            <x-ui.select name="branch" wire:model.live="branch" :options="$branches" :placeholder="__('finance.cash.own_branch')" />
        </x-ui.field>
    @endif

    @if (! $session)
        <x-ui.card :title="__('finance.cash.open_title')">
            <p class="mb-md text-text-subtle">{{ __('finance.cash.closed_hint') }}</p>
            <form wire:submit="open" class="flex flex-col gap-sm md:flex-row md:items-end" novalidate>
                <x-ui.field :label="__('finance.cash.opening') . ' (' . $currency . ')'" for="form.opening">
                    <x-ui.input name="form.opening" type="number" min="0" step="any" wire:model="form.opening" required />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="open">{{ __('finance.cash.open') }}</x-ui.button>
            </form>
        </x-ui.card>
    @else
        <x-ui.card :title="__('finance.cash.open_session', ['date' => $at($session->opened_at)])">
            <dl class="grid gap-md md:grid-cols-2">
                <div><dt class="text-caption text-text-subtle">{{ __('finance.cash.opening') }}</dt><dd>{{ $presenter->format($session->money($session->opening_amount_minor)) }}</dd></div>
                <div><dt class="text-caption text-text-subtle">{{ __('finance.cash.expected') }}</dt><dd class="text-heading-3 font-semibold">{{ $presenter->format($expected) }}</dd></div>
            </dl>

            @if ($session->movements->isEmpty())
                <p class="mt-md text-text-subtle">{{ __('finance.cash.no_movements') }}</p>
            @else
                <ul class="mt-md flex flex-col divide-y divide-border">
                    @foreach ($session->movements as $movement)
                        <li class="flex justify-between gap-sm py-xs" wire:key="movement-{{ $movement->ulid }}">
                            <span>{{ $movement->description }} <span class="text-caption text-text-subtle">{{ $movement->type->label() }} ·· {{ $at($movement->recorded_at) }}</span></span>
                            <span @class(['font-medium', 'text-success' => $movement->type === CashMovementType::Income, 'text-danger' => $movement->type !== CashMovementType::Income])>
                                {{ $movement->type === CashMovementType::Income ? '+' : '−' }} {{ $presenter->format($session->money($movement->amount_minor)) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        <x-ui.card :title="__('finance.cash.expense_title')">
            <form wire:submit="expense" class="grid gap-md md:grid-cols-4 md:items-end" novalidate>
                <x-ui.field :label="__('finance.cash.expense_type')" for="form.expense_type"><x-ui.select name="form.expense_type" wire:model="form.expense_type" :options="collect($outflows)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" /></x-ui.field>
                <x-ui.field :label="__('finance.cash.amount')" for="form.expense_amount"><x-ui.input name="form.expense_amount" type="number" min="0" step="any" wire:model="form.expense_amount" /></x-ui.field>
                <x-ui.field :label="__('finance.cash.description')" for="form.expense_description"><x-ui.input name="form.expense_description" wire:model="form.expense_description" /></x-ui.field>
                <x-ui.button type="submit" variant="secondary" wire:loading.attr="disabled" wire:target="expense">{{ __('finance.cash.record_expense') }}</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card :title="__('finance.cash.close_title')">
            <form wire:submit="close" class="grid gap-md md:grid-cols-3 md:items-end" novalidate>
                <x-ui.field :label="__('finance.cash.counted')" for="form.counted" :hint="__('finance.cash.counted_hint')"><x-ui.input name="form.counted" type="number" min="0" step="any" wire:model="form.counted" hint /></x-ui.field>
                <x-ui.field :label="__('finance.cash.note')" for="form.note"><x-ui.input name="form.note" wire:model="form.note" /></x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="close" wire:confirm="{{ __('finance.cash.confirm_close') }}">{{ __('finance.cash.close') }}</x-ui.button>
            </form>
        </x-ui.card>
    @endif

    <x-ui.card :title="__('finance.cash.history')">
        @forelse ($history as $closed)
            <p wire:key="closed-{{ $closed->ulid }}" class="py-xs">
                {{ $at($closed->opened_at) }} → {{ $at($closed->closed_at) }} ·
                {{ __('finance.cash.expected') }} {{ $presenter->format($closed->money((int) $closed->expected_amount_minor)) }} ·
                {{ __('finance.cash.counted') }} {{ $presenter->format($closed->money((int) $closed->counted_amount_minor)) }} ·
                <span @class(['font-medium', 'text-danger' => (int) $closed->difference_minor < 0, 'text-warning' => (int) $closed->difference_minor > 0, 'text-success' => (int) $closed->difference_minor === 0])>
                    {{ __('finance.cash.difference', ['amount' => $presenter->format($closed->money((int) $closed->difference_minor))]) }}
                </span>
            </p>
        @empty
            <p class="text-text-subtle">{{ __('finance.cash.no_history') }}</p>
        @endforelse
    </x-ui.card>
</div>
