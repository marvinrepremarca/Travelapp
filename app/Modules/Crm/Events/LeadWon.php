<?php

declare(strict_types=1);

namespace App\Modules\Crm\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un lead se ganó y quedó ligado a un cliente (lo escuchan cotizaciones, reportes, comisiones). */
final readonly class LeadWon implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'crm.lead_won';

    public function __construct(
        public string $leadUlid,
        public string $customerUlid,
        public int $ownerId,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
