<?php

declare(strict_types=1);

namespace App\Modules\Payments\Events;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un abono quedó aprobado (efectivo, transferencia validada o pago en línea confirmado por la pasarela). */
final readonly class PaymentReceived implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'payments.payment_received';

    public function __construct(
        public string $paymentUlid,
        public string $bookingUlid,
        public int $amountMinor,
        public string $currency,
        public PaymentMethod $method,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }

    public static function of(Payment $payment): self
    {
        return new self($payment->ulid, $payment->booking_ulid, $payment->amount_minor, $payment->currency, $payment->method);
    }
}
