<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Actions;

use App\Modules\Compliance\Data\ObligationData;
use App\Modules\Compliance\Exceptions\ComplianceRuleViolation;
use App\Modules\Compliance\Models\ComplianceObligation;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Marca cumplida una obligación y, si es periódica, crea la siguiente ocurrencia con el mismo responsable. */
final readonly class CompleteObligationAction
{
    public function __construct(private SaveObligationAction $save) {}

    public function execute(User $actor, ComplianceObligation $obligation): ?ComplianceObligation
    {
        return DB::transaction(function () use ($actor, $obligation): ?ComplianceObligation {
            $locked = ComplianceObligation::query()->whereKey($obligation->id)->lockForUpdate()->firstOrFail();
            throw_unless($locked->isPending(), ComplianceRuleViolation::alreadyCompleted());

            $locked->completed_at = CarbonImmutable::now();
            $locked->completed_by = $actor->id;
            $locked->save();

            $next = $locked->recurrence->next($locked->due_on);

            return $next === null ? null : $this->save->execute($actor, new ObligationData(
                $locked->title,
                $locked->description,
                $next,
                $locked->recurrence,
                $locked->responsible_id,
            ));
        });
    }
}
