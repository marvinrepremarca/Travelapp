@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    <div class="flex flex-col gap-md md:flex-row md:items-center md:justify-between">
        <p class="text-text-subtle">{{ __('finance.bank_accounts.hint') }}</p>
        <x-ui.link-button :href="route('finance.bank-accounts.create')">{{ __('finance.bank_accounts.create') }}</x-ui.link-button>
    </div>

    @if ($accounts->isEmpty())
        <x-ui.empty-state :title="__('finance.bank_accounts.empty_title')" :description="__('finance.bank_accounts.empty_description')" />
    @else
        <x-ui.table :caption="__('finance.bank_accounts.title')">
            <x-slot:head>
                <tr>
                    <th scope="col" class="px-md py-sm">{{ __('finance.bank_accounts.fields.name') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('finance.bank_accounts.fields.bank_name') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('finance.bank_accounts.fields.currency') }}</th>
                    <th scope="col" class="px-md py-sm">{{ __('finance.bank_accounts.pending') }}</th>
                    <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                </tr>
            </x-slot:head>
            @foreach ($accounts as $account)
                <tr wire:key="account-{{ $account->ulid }}">
                    <th scope="row" class="px-md py-sm text-left font-medium">
                        {{ $account->name }}
                        @unless ($account->is_active)<x-ui.badge :tone="Tone::Neutral">{{ __('finance.bank_accounts.inactive') }}</x-ui.badge>@endunless
                    </th>
                    <td class="px-md py-sm">{{ $account->bank_name }} ···{{ $account->account_last_digits }}</td>
                    <td class="px-md py-sm">{{ $account->currency }}</td>
                    <td class="px-md py-sm">
                        @php($count = $pending[$account->id] ?? 0)
                        <x-ui.badge :tone="$count > 0 ? Tone::Warning : Tone::Success">{{ trans_choice('finance.bank_accounts.pending_count', $count, ['count' => $count]) }}</x-ui.badge>
                    </td>
                    <td class="px-md py-sm">
                        <div class="flex flex-wrap gap-sm">
                            <a href="{{ route('finance.reconciliation', ['account' => $account->ulid]) }}" wire:navigate class="font-medium text-brand underline">{{ __('finance.bank_accounts.reconcile') }}</a>
                            <a href="{{ route('finance.bank-accounts.edit', $account) }}" wire:navigate class="text-brand underline">{{ __('shared.edit') }}<span class="sr-only"> {{ $account->name }}</span></a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</div>
