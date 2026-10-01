<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** El cliente aceptó una opción de una versión enviada (Bookings la convertirá en expediente). */
final readonly class QuoteAccepted
{
    use Dispatchable;

    public function __construct(
        public string $quoteUlid,
        public string $optionUlid,
        public int $version,
    ) {}
}
