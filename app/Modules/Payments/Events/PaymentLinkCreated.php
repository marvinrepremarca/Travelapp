<?php

declare(strict_types=1);

namespace App\Modules\Payments\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Se generó un link de pago para el cliente (para enviárselo por sus canales). */
final readonly class PaymentLinkCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $paymentUlid,
        public string $bookingUlid,
        public int $amountMinor,
        public string $currency,
        public string $url,
        public CarbonImmutable $expiresAt,
    ) {}
}
