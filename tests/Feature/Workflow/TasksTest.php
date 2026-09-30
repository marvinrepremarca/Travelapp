<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Actions\ChangeTaskStatusAction;
use App\Modules\Workflow\Actions\CreateTaskAction;
use App\Modules\Workflow\Actions\SendDueTaskRemindersAction;
use App\Modules\Workflow\Contracts\TaskScheduler;
use App\Modules\Workflow\Data\TaskData;
use App\Modules\Workflow\Enums\TaskPriority;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Exceptions\InvalidTask;
use App\Modules\Workflow\Livewire\TasksBoard;
use App\Modules\Workflow\Models\Task;
use App\Modules\Workflow\Notifications\TaskReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires authentication', function (): void {
    get(route('workflow.tasks'))->assertRedirect(route('login'));
});

it('creates a task for a colleague through the screen in the agency timezone', function (): void {
    config(['travel.agency.timezone' => 'America/Bogota']);
    $manager = branchManager();
    $colleague = User::factory()->for($manager->branch)->create();
    actingAs($manager);

    Livewire::test(TasksBoard::class)
        ->set('title', 'Confirmar hotel en Cartagena')
        ->set('assignee_id', (string) $colleague->id)
        ->set('priority', TaskPriority::High->value)
        ->set('due_at', '2026-10-20T09:00')
        ->set('remind_at', '2026-10-19T17:00')
        ->call('create')
        ->assertHasNoErrors();

    $task = Task::query()->sole();
    expect($task->owner_id)->toBe($colleague->id)
        ->and($task->branch_id)->toBe($manager->branch_id)
        ->and($task->created_by)->toBe($manager->id)
        ->and($task->status)->toBe(TaskStatus::Open)
        ->and($task->due_at?->toDateTimeString())->toBe('2026-10-20 14:00:00');
});

it('does not assign tasks outside the scope', function (): void {
    $agent = agent();
    $stranger = User::factory()->create();
    actingAs($agent);

    Livewire::test(TasksBoard::class)
        ->set('title', 'Tarea')
        ->set('assignee_id', (string) $stranger->id)
        ->call('create')
        ->assertHasErrors('assignee_id');
});

it('validates the reminder against the due date', function (): void {
    actingAs(agent());

    Livewire::test(TasksBoard::class)
        ->set('title', 'Tarea')
        ->set('due_at', '2026-10-20T09:00')
        ->set('remind_at', '2026-10-21T09:00')
        ->call('create')
        ->assertHasErrors(['remind_at' => __('workflow.errors.reminder_after_due')]);
});

it('lists only tasks inside the scope and filters by status and owner', function (): void {
    $agent = agent();
    $mine = Task::factory()->assignedTo($agent)->create(['title' => 'Mía abierta']);
    Task::factory()->assignedTo($agent)->done()->create(['title' => 'Mía terminada']);
    Task::factory()->assignedTo(User::factory()->create())->create(['title' => 'De otro asesor']);
    actingAs($agent);

    Livewire::test(TasksBoard::class)
        ->assertSee($mine->title)
        ->assertDontSee('De otro asesor')
        ->assertDontSee('Mía terminada')
        ->set('status', TaskStatus::Done->value)
        ->assertSee('Mía terminada')
        ->set('onlyMine', true)
        ->assertSee('Mía terminada');
});

it('marks overdue tasks and shows an empty state', function (): void {
    $agent = agent();
    Task::factory()->assignedTo($agent)->overdue()->create();
    actingAs($agent);

    Livewire::test(TasksBoard::class)
        ->assertSee(__('workflow.tasks.overdue'))
        ->set('status', TaskStatus::Cancelled->value)
        ->assertSee(__('workflow.tasks.empty_title'));
});

it('completes, reopens and cancels tasks', function (): void {
    $agent = agent();
    $task = Task::factory()->assignedTo($agent)->create();
    actingAs($agent);

    Livewire::test(TasksBoard::class)->call('changeStatus', $task->ulid, TaskStatus::Done->value);
    expect($task->fresh()?->status)->toBe(TaskStatus::Done)
        ->and($task->fresh()?->completed_at)->not->toBeNull();

    Livewire::test(TasksBoard::class)->call('changeStatus', $task->ulid, TaskStatus::Open->value);
    expect($task->fresh()?->completed_at)->toBeNull();

    Livewire::test(TasksBoard::class)->call('changeStatus', $task->ulid, TaskStatus::Cancelled->value);
    expect($task->fresh()?->status)->toBe(TaskStatus::Cancelled);
});

it('rejects invalid status transitions', function (): void {
    $agent = agent();
    $task = Task::factory()->assignedTo($agent)->done()->create();
    actingAs($agent);

    Livewire::test(TasksBoard::class)
        ->call('changeStatus', $task->ulid, TaskStatus::Cancelled->value)
        ->assertHasErrors('actions');

    expect(fn() => app(ChangeTaskStatusAction::class)->execute($task, TaskStatus::Done))->toThrow(InvalidTask::class);
});

it('answers not found for tasks outside the scope', function (): void {
    $task = Task::factory()->assignedTo(User::factory()->create())->create();
    actingAs(agent());

    Livewire::test(TasksBoard::class)->call('changeStatus', $task->ulid, TaskStatus::Done->value)->assertNotFound();
    Livewire::test(TasksBoard::class)->call('changeStatus', 'no-existe', TaskStatus::Done->value)->assertNotFound();
    expect($task->fresh()?->status)->toBe(TaskStatus::Open);
});

it('lets other modules schedule tasks through the contract', function (): void {
    $agent = agent();
    $subject = App\Modules\Organization\Models\Branch::factory()->create();

    $task = app(TaskScheduler::class)->schedule(new TaskData('Revisar sucursal', $agent->id, subject: $subject), userWithRole(Role::AgencyOwner));

    expect($task->subject_type)->toBe($subject->getMorphClass())
        ->and($task->subject_id)->toBe((string) $subject->id);
});

it('does not assign tasks to inactive users', function (): void {
    $inactive = User::factory()->create(['is_active' => false]);

    expect(fn() => app(CreateTaskAction::class)->execute(new TaskData('X', $inactive->id), agent()))
        ->toThrow(InvalidTask::class, __('workflow.errors.assignee_not_available'));
});

it('sends each due reminder once to the responsible user', function (): void {
    Notification::fake();
    $agent = agent();
    $due = Task::factory()->assignedTo($agent)->remindAt(now()->subMinute())->create();
    Task::factory()->assignedTo($agent)->remindAt(now()->addHour())->create();
    Task::factory()->assignedTo($agent)->done()->remindAt(now()->subMinute())->create();

    expect(app(SendDueTaskRemindersAction::class)->execute(CarbonImmutable::now()))->toBe(1)
        ->and(app(SendDueTaskRemindersAction::class)->execute(CarbonImmutable::now()))->toBe(0);

    Notification::assertSentTo($agent, TaskReminder::class, fn(TaskReminder $reminder): bool => $reminder->via($agent) === ['database'] && $reminder->toArray($agent)['task_ulid'] === $due->ulid);
    Notification::assertSentTimes(TaskReminder::class, 1);
});

it('runs the reminders command', function (): void {
    $this->artisan('workflow:send-task-reminders')->assertSuccessful();
});

it('labels task enums', function (): void {
    foreach ([...TaskStatus::cases(), ...TaskPriority::cases()] as $case) {
        expect($case->label())->not->toStartWith('workflow.');
    }

    expect(TaskPriority::Urgent->tone()->value)->toBe('danger')
        ->and(TaskStatus::Done->tone()->value)->toBe('success')
        ->and(InvalidTask::reminderAfterDue()->errorCode())->toBe('invalid_task');
});
