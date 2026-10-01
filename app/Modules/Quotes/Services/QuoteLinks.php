<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;

/** Enlace firmado para que el cliente vea y acepte la versión enviada; caduca con la vigencia de la cotización. */
final class QuoteLinks
{
    public const ROUTE = 'quotes.public';

    public function customerUrl(Quote $quote): ?string
    {
        if ($quote->current_version === 0 || ! $quote->valid_until instanceof CarbonImmutable) {
            return null;
        }

        return URL::temporarySignedRoute(self::ROUTE, $quote->valid_until, ['quote' => $quote->ulid, 'version' => $quote->current_version]);
    }
}
