<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Data\PaymentLinkRequest;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Events\PaymentLinkCreated;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\GatewayRegistry;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Genera un link de pago de la pasarela activa por un monto del saldo. El pago queda pendiente hasta el webhook.
 * El link se pide a la pasarela fuera de transacciones; la clave de idempotencia evita links duplicados en reintentos.
 */
final readonly class CreatePaymentLinkAction
{
    public function __construct(
        private GatewayRegistry $gateways,
        private RecordPaymentAction $record,
    ) {}

    public function execute(User $actor, BookingAccount $account, Money $amount, CarbonImmutable $now): Payment
    {
        $this->record->assertWithinBalance($account, $amount, $now);

        $gateway = $this->gateways->active();
        $ulid = (string) Str::ulid();
        $expiresAt = $now->addHours(config()->integer('travel.payments.link_ttl_hours'));
        $link = $gateway->createLink(new PaymentLinkRequest(
            paymentUlid: $ulid,
            amount: $amount,
            description: __('payments.link_description', ['number' => $account->number]),
            idempotencyKey: $ulid,
            expiresAt: $expiresAt,
        ));

        $payment = new Payment([
            'booking_ulid' => $account->ulid,
            'method' => PaymentMethod::OnlineLink,
            'amount_minor' => $amount->getMinorAmount()->toInt(),
            'currency' => $amount->getCurrency()->getCurrencyCode(),
        ]);
        $payment->ulid = $ulid;
        $payment->owner_id = $account->ownerId;
        $payment->branch_id = $account->branchId;
        $payment->status = PaymentStatus::Pending;
        $payment->gateway = $gateway->key();
        $payment->gateway_reference = $link->gatewayReference;
        $payment->link_url = $link->url;
        $payment->link_expires_at = $expiresAt;
        $payment->idempotency_key = $ulid;
        $payment->recorded_by = $actor->id;
        $payment->save();

        PaymentLinkCreated::dispatch($payment->ulid, $payment->booking_ulid, $payment->amount_minor, $payment->currency, $link->url, $expiresAt);

        return $payment;
    }
}
