<?php

declare(strict_types=1);

namespace App\Modules\Crm\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un lead se ganó y quedó ligado a un cliente (lo escuchan cotizaciones, reportes, comisiones). */
final readonly class LeadWon implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $leadUlid,
        public string $customerUlid,
        public int $ownerId,
    ) {}
}
