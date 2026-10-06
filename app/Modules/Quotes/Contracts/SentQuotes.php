<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Contracts;

use App\Modules\Quotes\Data\QuoteShare;

/** Enlace del cliente de una cotización enviada (para notificarlo por sus canales). */
interface SentQuotes
{
    /** Null si la cotización no existe, no está enviada o ya venció. */
    public function share(string $quoteUlid): ?QuoteShare;
}
