<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Providers;

use App\Modules\Shared\Routing\PathPrefix;
use App\Modules\Workflow\Actions\CreateTaskAction;
use App\Modules\Workflow\Actions\RequestApprovalAction;
use App\Modules\Workflow\Console\ExpireApprovalsCommand;
use App\Modules\Workflow\Console\SendTaskRemindersCommand;
use App\Modules\Workflow\Contracts\Approvals;
use App\Modules\Workflow\Contracts\TaskScheduler;
use App\Modules\Workflow\Livewire\ApprovalsInbox;
use App\Modules\Workflow\Livewire\TasksBoard;
use App\Modules\Workflow\Models\Task;
use App\Modules\Workflow\Policies\TaskPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class WorkflowServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        TaskScheduler::class => CreateTaskAction::class,
        Approvals::class => RequestApprovalAction::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'workflow');

        Livewire::component('workflow.tasks-board', TasksBoard::class);
        Livewire::component('workflow.approvals-inbox', ApprovalsInbox::class);

        Gate::policy(Task::class, TaskPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([SendTaskRemindersCommand::class, ExpireApprovalsCommand::class]);
        }

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(SendTaskRemindersCommand::class)->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command(ExpireApprovalsCommand::class)->everyFiveMinutes()->withoutOverlapping()->onOneServer();
        });
    }
}
