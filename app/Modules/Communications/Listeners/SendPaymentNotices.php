<?php

declare(strict_types=1);

namespace App\Modules\Communications\Listeners;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Services\CustomerNotifier;
use App\Modules\Payments\Events\PaymentLinkCreated;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;

/** Link de pago generado y abono recibido → aviso por WhatsApp al cliente del expediente. */
final readonly class SendPaymentNotices
{
    public function __construct(
        private BookingAccounts $accounts,
        private CustomerNotifier $notifier,
        private MoneyPresenter $presenter,
    ) {}

    public function handleLink(PaymentLinkCreated $event): void
    {
        $account = $this->accounts->account($event->bookingUlid);

        $this->notifier->notify($account->customerId, $account->ownerId, $account->branchId, NoticeTemplate::PaymentLink, [
            'number' => $account->number,
            'amount' => $this->presenter->format(Money::ofMinor($event->amountMinor, $event->currency)),
            'url' => $event->url,
            'expires' => $event->expiresAt->setTimezone(config()->string('travel.agency.timezone'))->translatedFormat(config()->string('travel.communications.notice_datetime_format')),
        ], NoticeTemplate::PaymentLink->value . ':' . $event->paymentUlid);
    }

    public function handleReceived(PaymentReceived $event): void
    {
        $account = $this->accounts->account($event->bookingUlid);

        $this->notifier->notify($account->customerId, $account->ownerId, $account->branchId, NoticeTemplate::PaymentReceived, [
            'number' => $account->number,
            'amount' => $this->presenter->format(Money::ofMinor($event->amountMinor, $event->currency)),
            'method' => $event->method->label(),
        ], NoticeTemplate::PaymentReceived->value . ':' . $event->paymentUlid);
    }
}
