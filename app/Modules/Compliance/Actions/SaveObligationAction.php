<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Compliance\Data\ObligationData;
use App\Modules\Compliance\Models\ComplianceObligation;
use App\Modules\Compliance\Services\ComplianceTasks;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/** Crea o edita una obligación del calendario; con fecha nueva o responsable nuevo se programa su tarea con recordatorio ⚙. */
final readonly class SaveObligationAction
{
    public function __construct(private ComplianceTasks $tasks) {}

    public function execute(User $actor, ObligationData $data, ?ComplianceObligation $obligation = null): ComplianceObligation
    {
        return DB::transaction(function () use ($actor, $data, $obligation): ComplianceObligation {
            $obligation ??= new ComplianceObligation();
            $obligation->fill([
                'title' => $data->title,
                'description' => $data->description,
                'due_on' => $data->dueOn,
                'recurrence' => $data->recurrence,
                'responsible_id' => $data->responsibleId,
            ]);
            $needsTask = ! $obligation->exists || $obligation->isDirty(['due_on', 'responsible_id']);
            if (! $obligation->exists) {
                $obligation->created_by = $actor->id;
            }
            $obligation->save();

            if ($needsTask) {
                $this->schedule($actor, $obligation);
            }

            return $obligation;
        });
    }

    public function schedule(User $actor, ComplianceObligation $obligation): void
    {
        $this->tasks->schedule(
            __('compliance.obligations.task', ['title' => $obligation->title]),
            $obligation->responsible_id,
            $obligation->due_on,
            config()->integer('travel.compliance.obligation_alert_days'),
            $obligation,
            $actor,
        );
    }
}
