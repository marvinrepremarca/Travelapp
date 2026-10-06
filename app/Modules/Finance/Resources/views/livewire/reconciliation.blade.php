@php
    use App\Modules\Finance\Enums\StatementLineStatus;
    use App\Modules\Shared\Enums\Tone;
    $date = fn ($value) => $value->locale(app()->getLocale())->isoFormat('ll');
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif

    @if ($accounts->isEmpty())
        <x-ui.empty-state :title="__('finance.reconciliation.no_accounts')" :description="__('finance.reconciliation.no_accounts_hint')" />
        <div><x-ui.link-button :href="route('finance.bank-accounts.create')">{{ __('finance.bank_accounts.create') }}</x-ui.link-button></div>
    @else
        <div class="grid gap-md md:grid-cols-2 md:items-end">
            <x-ui.field :label="__('finance.reconciliation.account')" for="account">
                <x-ui.select name="account" wire:model.live="account" :options="$accounts->mapWithKeys(fn ($item) => [$item->ulid => $item->label()])->all()" />
            </x-ui.field>
            <form wire:submit="import" class="flex flex-col gap-sm md:flex-row md:items-end" novalidate>
                <x-ui.field :label="__('finance.reconciliation.file')" for="statementFile" :hint="__('finance.reconciliation.file_hint')">
                    <x-ui.input name="statementFile" type="file" accept=".csv,text/csv,text/plain" wire:model="statementFile" hint />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="import,statementFile">{{ __('finance.reconciliation.import') }}</x-ui.button>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-sm" role="group" aria-label="{{ __('finance.reconciliation.status_filter') }}">
            @foreach ($statuses as $option)
                <x-ui.button type="button" :variant="$status === $option->value ? 'primary' : 'secondary'" wire:click="$set('status', '{{ $option->value }}')" :aria-pressed="$status === $option->value ? 'true' : 'false'">
                    {{ $option->label() }} ({{ $counts[$option->value] ?? 0 }})
                </x-ui.button>
            @endforeach
        </div>

        <div wire:loading.delay wire:target="account,status,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="5" /></div>

        <div wire:loading.remove wire:target="account,status,gotoPage,nextPage,previousPage" class="flex flex-col gap-lg">
            @if ($lines === null || $lines->isEmpty())
                <x-ui.empty-state :title="__('finance.reconciliation.empty')" :description="__('finance.reconciliation.empty_hint')" />
            @else
                <ul class="flex flex-col gap-sm">
                    @foreach ($lines as $line)
                        <li wire:key="line-{{ $line->ulid }}" class="flex flex-col gap-sm rounded-card border border-border bg-surface p-md">
                            <div class="flex flex-wrap items-start justify-between gap-sm">
                                <div>
                                    <p class="font-medium">{{ $line->description }}</p>
                                    <p class="text-caption text-text-subtle">{{ $date($line->posted_on) }}@if ($line->reference) · {{ __('finance.reconciliation.reference', ['reference' => $line->reference]) }}@endif</p>
                                </div>
                                <div class="flex items-center gap-sm">
                                    <span @class(['font-semibold', 'text-success' => $line->isInflow(), 'text-danger' => ! $line->isInflow()])>
                                        {{ $line->isInflow() ? __('finance.reconciliation.inflow') : __('finance.reconciliation.outflow') }} {{ $presenter->format($line->amount()->abs()) }}
                                    </span>
                                    <x-ui.badge :tone="$line->status->tone()">{{ $line->status->label() }}</x-ui.badge>
                                </div>
                            </div>

                            @if ($line->status === StatementLineStatus::Pending)
                                @php($lineOptions = $options[$line->ulid] ?? [])
                                <div class="grid gap-sm md:grid-cols-2 md:items-end">
                                    <div class="flex flex-col gap-sm md:flex-row md:items-end">
                                        <x-ui.field :label="__('finance.reconciliation.match_with')" for="choices.{{ $line->ulid }}">
                                            @if ($lineOptions === [])
                                                <p class="text-caption text-text-subtle">{{ __('finance.reconciliation.no_candidates') }}</p>
                                            @else
                                                <x-ui.select name="choices.{{ $line->ulid }}" wire:model="choices.{{ $line->ulid }}" :placeholder="__('finance.reconciliation.choose_entry')"
                                                    :options="collect($lineOptions)->mapWithKeys(fn ($entry) => [$entry->key() => (in_array($entry->key(), $suggestions[$line->ulid] ?? [], true) ? __('finance.reconciliation.suggested') . ' · ' : '') . $entry->label . ' · ' . $date($entry->occurredOn)])->all()" />
                                            @endif
                                        </x-ui.field>
                                        @if ($lineOptions !== [])
                                            <x-ui.button type="button" wire:click="match('{{ $line->ulid }}')" wire:loading.attr="disabled" wire:target="match('{{ $line->ulid }}')">{{ __('finance.reconciliation.confirm') }}</x-ui.button>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-sm md:flex-row md:items-end">
                                        <x-ui.field :label="__('finance.reconciliation.note')" for="notes.{{ $line->ulid }}" :hint="__('finance.reconciliation.ignore_hint')">
                                            <x-ui.input name="notes.{{ $line->ulid }}" wire:model="notes.{{ $line->ulid }}" hint />
                                        </x-ui.field>
                                        <x-ui.button type="button" variant="secondary" wire:click="ignore('{{ $line->ulid }}')" wire:loading.attr="disabled" wire:target="ignore('{{ $line->ulid }}')">{{ __('finance.reconciliation.ignore') }}</x-ui.button>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center justify-between gap-sm text-caption text-text-subtle">
                                    <span>
                                        @if ($line->status === StatementLineStatus::Matched)
                                            {{ __('finance.reconciliation.matched_with', ['target' => $line->matched_target?->label()]) }}
                                        @else
                                            {{ $line->note }}
                                        @endif
                                    </span>
                                    <x-ui.button type="button" variant="secondary" wire:click="reopen('{{ $line->ulid }}')" wire:confirm="{{ __('finance.reconciliation.confirm_reopen') }}" wire:loading.attr="disabled" wire:target="reopen('{{ $line->ulid }}')">{{ __('finance.reconciliation.reopen') }}</x-ui.button>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div>{{ $lines->links() }}</div>
            @endif

            <x-ui.card :title="__('finance.reconciliation.unmatched_title')">
                <p class="mb-md text-caption text-text-subtle">{{ __('finance.reconciliation.unmatched_hint') }}</p>
                @forelse ($unmatched as $entry)
                    <p wire:key="entry-{{ $entry->key() }}" class="flex flex-wrap justify-between gap-sm border-b border-border py-xs">
                        <span>{{ $entry->label }} <span class="text-caption text-text-subtle">· {{ $date($entry->occurredOn) }}</span></span>
                        <span class="font-medium">{{ $presenter->format($entry->amount) }}</span>
                    </p>
                @empty
                    <p class="text-text-subtle">{{ __('finance.reconciliation.all_matched') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    @endif
</div>
