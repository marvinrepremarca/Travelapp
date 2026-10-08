<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Foundation\Events\Dispatchable;

/** El cliente aceptó una opción de una versión enviada (Bookings la convertirá en expediente). */
final readonly class QuoteAccepted implements IntegrationEvent
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'quotes.quote_accepted';

    public function __construct(
        public string $quoteUlid,
        public string $optionUlid,
        public int $version,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
