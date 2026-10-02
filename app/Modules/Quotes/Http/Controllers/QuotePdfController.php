<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Controllers;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Quotes\Services\QuoteDocument;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

/** Descarga del PDF de una versión enviada: el asesor (por alcance) o el cliente (enlace firmado). */
final class QuotePdfController
{
    public function internal(Quote $quote, int $version, QuoteDocument $document): PdfBuilder
    {
        Gate::authorize('view', $quote);

        return $document->pdf($quote, $this->version($quote, $version))->download();
    }

    public function customer(Quote $quote, int $version, QuoteDocument $document): PdfBuilder
    {
        return $document->pdf($quote, $this->version($quote, $version))->download();
    }

    private function version(Quote $quote, int $version): QuoteVersion
    {
        return QuoteVersion::query()->where('quote_id', $quote->id)->where('version', $version)->firstOrFail();
    }
}
