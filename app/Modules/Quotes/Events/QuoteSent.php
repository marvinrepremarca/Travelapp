<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Foundation\Events\Dispatchable;

/** Una versión de la cotización salió al cliente (otros módulos reaccionan: CRM, notificaciones). */
final readonly class QuoteSent implements IntegrationEvent
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'quotes.quote_sent';

    public function __construct(
        public string $quoteUlid,
        public int $version,
        // Prospecto que originó la cotización; nulo en cotizaciones sin prospecto y en eventos anteriores.
        public ?string $leadUlid = null,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
