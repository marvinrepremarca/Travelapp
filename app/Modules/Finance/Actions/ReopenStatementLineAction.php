<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Services\StatementLineLedger;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Deshace un cruce o un "ignorar" equivocado: la línea vuelve a quedar por conciliar (queda auditado). */
final readonly class ReopenStatementLineAction
{
    public function __construct(private StatementLineLedger $ledger) {}

    public function execute(User $actor, string $lineUlid, CarbonImmutable $now): BankStatementLine
    {
        return DB::transaction(function () use ($actor, $lineUlid, $now): BankStatementLine {
            $line = $this->ledger->lock($lineUlid);
            $line->status = StatementLineStatus::Pending;
            $line->matched_target = null;
            $line->matched_ulid = null;
            $line->note = null;

            return $this->ledger->save($line, $actor, $now);
        });
    }
}
