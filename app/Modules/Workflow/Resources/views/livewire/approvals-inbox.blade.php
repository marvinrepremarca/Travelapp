<div class="flex flex-col gap-section">
    <section class="flex flex-col gap-md" aria-labelledby="pending-heading">
        <h2 id="pending-heading" class="text-heading-3 font-semibold">{{ __('workflow.approvals.pending_for_me') }}</h2>

        @if ($pendingForMe->isEmpty())
            <x-ui.empty-state :title="__('workflow.approvals.nothing_pending')" />
        @else
            @foreach ($pendingForMe as $approval)
                <x-ui.card wire:key="pending-{{ $approval->ulid }}">
                    <div class="flex flex-col gap-sm">
                        <div class="flex flex-wrap items-center gap-sm">
                            <x-ui.badge :tone="$approval->status->tone()">{{ $approval->type->label() }}</x-ui.badge>
                            <span class="text-caption text-text-subtle">
                                {{ __('workflow.approvals.requested_by', ['name' => $requesters[$approval->owner_id] ?? '—', 'date' => $approval->created_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}
                            </span>
                        </div>
                        <p class="font-medium">{{ $approval->summary }}</p>
                        @if ($approval->justification)
                            <p class="text-text-subtle">{{ $approval->justification }}</p>
                        @endif
                        <x-ui.field :label="__('workflow.approvals.note')" for="notes.{{ $approval->ulid }}" :hint="__('workflow.approvals.note_hint')"
                            :error="$errors->first('notes.'.$approval->ulid)">
                            <x-ui.input name="notes.{{ $approval->ulid }}" wire:model="notes.{{ $approval->ulid }}" hint />
                        </x-ui.field>
                        <div class="flex justify-end gap-sm">
                            <x-ui.button variant="danger" wire:click="reject('{{ $approval->ulid }}')" wire:loading.attr="disabled">
                                {{ __('workflow.approvals.reject') }}<span class="sr-only"> {{ $approval->summary }}</span>
                            </x-ui.button>
                            <x-ui.button wire:click="approve('{{ $approval->ulid }}')" wire:loading.attr="disabled">
                                {{ __('workflow.approvals.approve') }}<span class="sr-only"> {{ $approval->summary }}</span>
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        @endif
    </section>

    <section class="flex flex-col gap-md" aria-labelledby="mine-heading">
        <h2 id="mine-heading" class="text-heading-3 font-semibold">{{ __('workflow.approvals.my_requests') }}</h2>

        @if ($myRequests->isEmpty())
            <x-ui.empty-state :title="__('workflow.approvals.no_requests')" />
        @else
            <x-ui.table :caption="__('workflow.approvals.my_requests')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('workflow.approvals.columns.type') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('workflow.approvals.columns.summary') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('workflow.approvals.columns.status') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('workflow.approvals.columns.note') }}</th>
                        <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($myRequests as $approval)
                    <tr wire:key="mine-{{ $approval->ulid }}">
                        <td class="px-md py-sm">{{ $approval->type->label() }}</td>
                        <td class="px-md py-sm">{{ $approval->summary }}</td>
                        <td class="px-md py-sm"><x-ui.badge :tone="$approval->status->tone()">{{ $approval->status->label() }}</x-ui.badge></td>
                        <td class="px-md py-sm">{{ $approval->decision_note }}</td>
                        <td class="px-md py-sm">
                            @unless ($approval->status->isFinal())
                                <x-ui.button variant="ghost" wire:click="cancel('{{ $approval->ulid }}')" wire:loading.attr="disabled"
                                    wire:confirm="{{ __('workflow.approvals.confirm_cancel') }}">
                                    {{ __('workflow.approvals.cancel') }}<span class="sr-only"> {{ $approval->summary }}</span>
                                </x-ui.button>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
    </section>
</div>
