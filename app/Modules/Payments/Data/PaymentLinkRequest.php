<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

final readonly class PaymentLinkRequest
{
    public function __construct(
        public string $paymentUlid,
        public Money $amount,
        public string $description,
        public string $idempotencyKey,
        public CarbonImmutable $expiresAt,
    ) {}
}
