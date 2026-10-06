<?php

declare(strict_types=1);

namespace App\Modules\Communications\Actions;

use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Communications\Services\CustomerNotifier;
use App\Modules\Payments\Contracts\UpcomingBalances;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;

/**
 * Recordatorio de saldo: N días ⚙ antes de la fecha límite de pago, a los clientes con saldo pendiente.
 * Corre a diario; la clave por expediente y fecha evita repetirlo. Devuelve cuántos avisos salieron.
 */
final readonly class SendBalanceRemindersAction
{
    public function __construct(
        private UpcomingBalances $balances,
        private CustomerNotifier $notifier,
        private MoneyPresenter $presenter,
    ) {}

    public function execute(CarbonImmutable $today): int
    {
        $dueDate = $today->startOfDay()->addDays(config()->integer('travel.communications.balance_reminder_days_before'));
        $sent = 0;
        foreach ($this->balances->dueOn($dueDate) as $due) {
            $message = $this->notifier->notify($due->customerId, $due->ownerId, $due->branchId, NoticeTemplate::BalanceReminder, [
                'number' => $due->bookingNumber,
                'amount' => $this->presenter->format($due->balance),
                'due' => $due->dueDate->translatedFormat(config()->string('travel.communications.notice_date_format')),
            ], NoticeTemplate::BalanceReminder->value . ':' . $due->bookingUlid . ':' . $due->dueDate->toDateString());
            $sent += $message instanceof ConversationMessage ? 1 : 0;
        }

        return $sent;
    }
}
