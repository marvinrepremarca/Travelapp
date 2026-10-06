<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Pasos comunes al decidir una línea del extracto (se usan dentro de la transacción de cada Action). */
final class StatementLineLedger
{
    public function lock(string $lineUlid): BankStatementLine
    {
        return BankStatementLine::query()->where('ulid', $lineUlid)->lockForUpdate()->firstOrFail();
    }

    public function lockPending(string $lineUlid): BankStatementLine
    {
        $line = $this->lock($lineUlid);
        if ($line->status !== StatementLineStatus::Pending) {
            throw FinanceRuleViolation::lineNotPending();
        }

        return $line;
    }

    public function save(BankStatementLine $line, User $actor, CarbonImmutable $now): BankStatementLine
    {
        $line->resolved_by = $actor->id;
        $line->resolved_at = $now;
        $line->save();

        return $line;
    }
}
