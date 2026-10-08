<?php

declare(strict_types=1);

namespace App\Modules\Payments\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Se generó un link de pago para el cliente (para enviárselo por sus canales). */
final readonly class PaymentLinkCreated implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'payments.link_created';

    public function __construct(
        public string $paymentUlid,
        public string $bookingUlid,
        public int $amountMinor,
        public string $currency,
        public string $url,
        public CarbonImmutable $expiresAt,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
