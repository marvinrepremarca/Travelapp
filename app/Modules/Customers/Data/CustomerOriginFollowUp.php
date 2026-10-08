<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

use App\Modules\Shared\Enums\SalesChannel;

/** Siguiente paso tras crear un cliente desde su origen: empezar la cotización con este título y canal. */
final readonly class CustomerOriginFollowUp
{
    public function __construct(
        public string $quoteTitle,
        public SalesChannel $channel,
    ) {}
}
