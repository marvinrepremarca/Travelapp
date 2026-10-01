<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Una versión de la cotización salió al cliente (otros módulos reaccionan: CRM, notificaciones). */
final readonly class QuoteSent
{
    use Dispatchable;

    public function __construct(
        public string $quoteUlid,
        public int $version,
    ) {}
}
