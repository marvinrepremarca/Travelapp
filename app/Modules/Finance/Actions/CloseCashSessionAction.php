<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Cierra la caja con el arqueo: registra lo esperado, lo contado y la diferencia (sobrante o faltante). */
final readonly class CloseCashSessionAction
{
    public function __construct(private CashDesk $desk) {}

    public function execute(User $actor, CashSession $session, Money $counted, ?string $note, CarbonImmutable $now): CashSession
    {
        return DB::transaction(function () use ($actor, $session, $counted, $note, $now): CashSession {
            $session = CashSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($session->status !== CashSessionStatus::Open) {
                throw FinanceRuleViolation::cashSessionClosed();
            }

            $expected = $this->desk->expected($session);
            $session->status = CashSessionStatus::Closed;
            $session->open_branch_key = null;
            $session->expected_amount_minor = $expected->getMinorAmount()->toInt();
            $session->counted_amount_minor = $counted->getMinorAmount()->toInt();
            $session->difference_minor = $counted->minus($expected)->getMinorAmount()->toInt();
            $session->closing_note = $note;
            $session->closed_by = $actor->id;
            $session->closed_at = $now;
            $session->save();

            return $session;
        });
    }
}
