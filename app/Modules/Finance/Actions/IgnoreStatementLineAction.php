<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Services\StatementLineLedger;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Marca una línea del banco sin contrapartida en el sistema (comisiones, GMF, intereses), con nota obligatoria. */
final readonly class IgnoreStatementLineAction
{
    public function __construct(private StatementLineLedger $ledger) {}

    public function execute(User $actor, string $lineUlid, string $note, CarbonImmutable $now): BankStatementLine
    {
        return DB::transaction(function () use ($actor, $lineUlid, $note, $now): BankStatementLine {
            $line = $this->ledger->lockPending($lineUlid);
            $line->status = StatementLineStatus::Ignored;
            $line->note = $note;

            return $this->ledger->save($line, $actor, $now);
        });
    }
}
