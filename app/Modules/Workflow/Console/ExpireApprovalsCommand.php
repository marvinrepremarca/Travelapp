<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Console;

use App\Modules\Workflow\Actions\ExpireApprovalsAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class ExpireApprovalsCommand extends Command
{
    protected $signature = 'workflow:expire-approvals';

    protected $description = 'Marca como vencidas las aprobaciones pendientes cuyo plazo pasó.';

    public function handle(ExpireApprovalsAction $expire): int
    {
        $this->info(__('workflow.approvals_expired', ['count' => $expire->execute(CarbonImmutable::now())]));

        return self::SUCCESS;
    }
}
