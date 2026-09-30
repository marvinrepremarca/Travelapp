@php
    use App\Modules\Shared\Enums\Tone;
    use App\Modules\Workflow\Enums\TaskStatus;
@endphp
<div class="grid gap-lg lg:grid-cols-3">
    <div class="flex flex-col gap-md lg:col-span-2">
        @error('actions')
            <x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>
        @enderror

        <div class="flex flex-col gap-md md:flex-row md:items-end">
            <x-ui.field :label="__('workflow.tasks.fields.status')" for="status">
                <x-ui.select name="status" wire:model.live="status"
                    :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            </x-ui.field>
            <label class="flex items-center gap-sm text-body">
                <input type="checkbox" wire:model.live="onlyMine" class="rounded-control border-border">
                {{ __('workflow.tasks.only_mine') }}
            </label>
        </div>

        <div wire:loading.delay wire:target="status,onlyMine,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

        <div wire:loading.remove wire:target="status,onlyMine,gotoPage,nextPage,previousPage">
            @if ($tasks->isEmpty())
                <x-ui.empty-state :title="__('workflow.tasks.empty_title')" :description="__('workflow.tasks.empty_description')" />
            @else
                <ul class="flex flex-col gap-sm">
                    @foreach ($tasks as $task)
                        <li wire:key="task-{{ $task->ulid }}">
                            <x-ui.card>
                                <div class="flex flex-col gap-sm md:flex-row md:items-start md:justify-between">
                                    <div class="flex flex-col gap-xs">
                                        <p class="font-medium">{{ $task->title }}</p>
                                        @if ($task->description)
                                            <p class="text-text-subtle">{{ $task->description }}</p>
                                        @endif
                                        <div class="flex flex-wrap items-center gap-sm text-caption text-text-subtle">
                                            <x-ui.badge :tone="$task->priority->tone()">{{ $task->priority->label() }}</x-ui.badge>
                                            @if ($task->isOverdue($now))
                                                <x-ui.badge :tone="Tone::Danger">{{ __('workflow.tasks.overdue') }}</x-ui.badge>
                                            @endif
                                            @if ($task->due_at)
                                                <span>{{ __('workflow.tasks.due', ['date' => $task->due_at->setTimezone($timezone)->locale(app()->getLocale())->isoFormat('lll')]) }}</span>
                                            @endif
                                            <span>{{ __('workflow.tasks.assigned_to', ['name' => $owners[$task->owner_id] ?? '—']) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex gap-sm">
                                        @foreach ($task->status->allowedTransitions() as $next)
                                            <x-ui.button :variant="$next === TaskStatus::Done ? 'primary' : 'secondary'"
                                                wire:click="changeStatus('{{ $task->ulid }}', '{{ $next->value }}')" wire:loading.attr="disabled"
                                                wire:key="task-{{ $task->ulid }}-{{ $next->value }}">
                                                {{ __("workflow.tasks.transition.{$next->value}") }}<span class="sr-only"> {{ $task->title }}</span>
                                            </x-ui.button>
                                        @endforeach
                                    </div>
                                </div>
                            </x-ui.card>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-md">{{ $tasks->links() }}</div>
            @endif
        </div>
    </div>

    <x-ui.card :title="__('workflow.tasks.new')">
        <form wire:submit="create" class="flex flex-col gap-md" novalidate>
            <x-ui.field :label="__('workflow.tasks.fields.title')" for="title">
                <x-ui.input name="title" wire:model="title" required />
            </x-ui.field>
            <x-ui.field :label="__('workflow.tasks.fields.description')" for="description">
                <x-ui.input name="description" wire:model="description" />
            </x-ui.field>
            <x-ui.field :label="__('workflow.tasks.fields.assignee_id')" for="assignee_id">
                <x-ui.select name="assignee_id" wire:model="assignee_id" :options="$assignees" />
            </x-ui.field>
            <x-ui.field :label="__('workflow.tasks.fields.priority')" for="priority">
                <x-ui.select name="priority" wire:model="priority"
                    :options="collect($priorities)->mapWithKeys(fn ($priority) => [$priority->value => $priority->label()])->all()" />
            </x-ui.field>
            <x-ui.field :label="__('workflow.tasks.fields.due_at')" for="due_at" :hint="__('workflow.tasks.timezone_hint', ['timezone' => $timezone])">
                <x-ui.input name="due_at" type="datetime-local" wire:model="due_at" hint />
            </x-ui.field>
            <x-ui.field :label="__('workflow.tasks.fields.remind_at')" for="remind_at">
                <x-ui.input name="remind_at" type="datetime-local" wire:model="remind_at" />
            </x-ui.field>
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="create">{{ __('workflow.tasks.create') }}</x-ui.button>
        </form>
    </x-ui.card>
</div>
