<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Workflow\Actions\ChangeTaskStatusAction;
use App\Modules\Workflow\Actions\CreateTaskAction;
use App\Modules\Workflow\Data\TaskData;
use App\Modules\Workflow\Enums\TaskPriority;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Exceptions\InvalidTask;
use App\Modules\Workflow\Models\Task;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.backoffice')]
final class TasksBoard extends Component
{
    use WithPagination;

    #[Url(except: 'open')]
    public string $status = 'open';

    #[Url(except: false)]
    public bool $onlyMine = false;

    public string $title = '';

    public string $description = '';

    public string $priority = 'normal';

    public string $due_at = '';

    public string $remind_at = '';

    public string $assignee_id = '';

    public function mount(): void
    {
        $this->assignee_id = (string) $this->actor()->id;
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(CreateTaskAction $create): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date'],
            'remind_at' => ['nullable', 'date'],
            'assignee_id' => ['required', 'integer', Rule::in(array_keys($this->assignableUsers()))],
        ], attributes: $this->attributes());

        try {
            $create->execute(new TaskData(
                title: $validated['title'],
                assigneeId: (int) $validated['assignee_id'],
                priority: TaskPriority::from($validated['priority']),
                description: $validated['description'] ?: null,
                dueAt: $this->toUtc($validated['due_at']),
                remindAt: $this->toUtc($validated['remind_at']),
            ), $this->actor());
        } catch (InvalidTask $exception) {
            $this->addError('remind_at', $exception->getMessage());

            return;
        }

        $this->reset('title', 'description', 'due_at', 'remind_at');
        $this->priority = TaskPriority::Normal->value;
        session()->flash('status', __('workflow.tasks.created'));
    }

    public function changeStatus(string $ulid, string $status, ChangeTaskStatusAction $change): void
    {
        $task = Task::query()->where('ulid', $ulid)->first() ?? abort(404);
        Gate::authorize('update', $task);

        $this->resetErrorBag('actions');

        try {
            $change->execute($task, TaskStatus::from($status));
        } catch (InvalidTask $exception) {
            $this->addError('actions', $exception->getMessage());
        }
    }

    public function render(): View
    {
        $status = TaskStatus::tryFrom($this->status) ?? TaskStatus::Open;

        $tasks = Task::query()
            ->visibleTo($this->actor())
            ->where('status', $status)
            ->when($this->onlyMine, fn(Builder $query) => $query->where('owner_id', $this->actor()->id))
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->paginate(config()->integer('travel.workflow.per_page'));

        $owners = User::query()->whereIn('id', $tasks->getCollection()->pluck('owner_id'))->pluck('name', 'id');

        return view('workflow::livewire.tasks-board', [
            'tasks' => $tasks,
            'owners' => $owners,
            'statuses' => TaskStatus::cases(),
            'priorities' => TaskPriority::cases(),
            'assignees' => $this->assignableUsers(),
            'now' => CarbonImmutable::now(),
            'timezone' => $this->timezone(),
        ])->title(__('workflow.tasks.title'))
            ->layoutData(['heading' => __('workflow.tasks.title')]);
    }

    /**
     * Se puede asignar a uno mismo o a usuarios activos dentro del propio alcance.
     *
     * @return array<int, string>
     */
    private function assignableUsers(): array
    {
        return User::query()->visibleTo($this->actor())->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()
            + [$this->actor()->id => $this->actor()->name];
    }

    /** Las fechas se digitan en la zona de la agencia y se guardan en UTC. */
    private function toUtc(?string $value): ?CarbonImmutable
    {
        return $value === null || $value === '' ? null : CarbonImmutable::parse($value, $this->timezone())->utc();
    }

    private function timezone(): string
    {
        return app(AppSettings::class)->agencyTimezone();
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('workflow.tasks.fields');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
