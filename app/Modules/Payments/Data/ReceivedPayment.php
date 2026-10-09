<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use App\Modules\Payments\Enums\PaymentMethod;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Abono aprobado que debió entrar al banco (transferencia o pago en línea), para la conciliación de Finance. */
final readonly class ReceivedPayment
{
    public function __construct(
        public string $ulid,
        public ?string $bookingUlid,
        public PaymentMethod $method,
        public Money $amount,
        public CarbonImmutable $approvedAt,
        public ?string $reference,
    ) {}
}
