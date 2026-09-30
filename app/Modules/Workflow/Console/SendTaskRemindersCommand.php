<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Console;

use App\Modules\Workflow\Actions\SendDueTaskRemindersAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SendTaskRemindersCommand extends Command
{
    protected $signature = 'workflow:send-task-reminders';

    protected $description = 'Envía los recordatorios de tareas vencidos (se programa cada minuto).';

    public function handle(SendDueTaskRemindersAction $send): int
    {
        $sent = $send->execute(CarbonImmutable::now());
        $this->info(__('workflow.reminders_sent', ['count' => $sent]));

        return self::SUCCESS;
    }
}
