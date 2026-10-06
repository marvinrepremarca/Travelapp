<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\ReconciliationTarget;
use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Services\ReconciliationCandidates;
use App\Modules\Finance\Services\StatementLineLedger;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/** Concilia una línea del extracto con un movimiento del sistema del mismo valor y moneda (cada movimiento, una sola vez). */
final readonly class MatchStatementLineAction
{
    public function __construct(
        private ReconciliationCandidates $candidates,
        private StatementLineLedger $ledger,
    ) {}

    public function execute(User $actor, string $lineUlid, ReconciliationTarget $target, string $entryUlid, CarbonImmutable $now): BankStatementLine
    {
        $entry = $this->candidates->find($target, $entryUlid);

        try {
            return DB::transaction(function () use ($actor, $lineUlid, $target, $entry, $now): BankStatementLine {
                $line = $this->ledger->lockPending($lineUlid);
                if (!$entry instanceof \App\Modules\Finance\Data\ReconciliationEntry || ! $entry->amount->isEqualTo($line->amount())) {
                    throw FinanceRuleViolation::matchNotValid();
                }

                $line->status = StatementLineStatus::Matched;
                $line->matched_target = $target;
                $line->matched_ulid = $entry->ulid;

                return $this->ledger->save($line, $actor, $now);
            });
        } catch (UniqueConstraintViolationException) {
            throw FinanceRuleViolation::matchNotValid();
        }
    }
}
