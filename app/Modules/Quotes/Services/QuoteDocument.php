<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Documents\Contracts\DocumentRenderer;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use Brick\Money\Money;
use Spatie\LaravelPdf\PdfBuilder;

/** PDF de una versión enviada: lo mismo que vio el cliente (opciones, itinerario, totales de venta). Nunca neto ni margen. */
final readonly class QuoteDocument
{
    private const VIEW = 'quotes::pdf.quote';

    public function __construct(
        private DocumentRenderer $renderer,
        private ItineraryBuilder $itinerary,
    ) {}

    public function pdf(Quote $quote, QuoteVersion $version): PdfBuilder
    {
        return $this->renderer->pdf(self::VIEW, $this->data($quote, $version), __('quotes.pdf.filename', ['number' => $quote->number, 'version' => $version->version]));
    }

    public function html(Quote $quote, QuoteVersion $version): string
    {
        return $this->renderer->html(self::VIEW, $this->data($quote, $version));
    }

    /** @return array<string, mixed> */
    private function data(Quote $quote, QuoteVersion $version): array
    {
        $currency = $version->snapshot['currency'];

        return [
            'documentTitle' => __('quotes.pdf.title', ['number' => $quote->number]),
            'quote' => $quote->loadMissing('customer:id,display_name'),
            'version' => $version,
            'options' => array_map(fn(array $option): array => [
                ...$option,
                'total' => Money::ofMinor($option['sale_total_minor'], $currency),
                'itinerary' => $this->itinerary->build($option['items']),
                'lines' => array_map(static fn(array $item): array => [...$item, 'sale' => Money::ofMinor($item['sale_amount_minor'], $currency)], $option['items']),
            ], $version->snapshot['options']),
        ];
    }
}
