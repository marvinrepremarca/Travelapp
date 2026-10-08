<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Quotes\Services\QuoteLifecycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Envía el borrador: congela una versión inmutable con opciones, ítems y precios, y fija la vigencia ⚙.
 * Ninguna opción puede ir vacía.
 */
final readonly class SendQuoteAction
{
    public function __construct(
        private QuoteLifecycle $lifecycle,
        private AppSettings $settings,
    ) {}

    public function execute(Quote $quote, User $actor, CarbonImmutable $now): QuoteVersion
    {
        $version = DB::transaction(function () use ($quote, $actor, $now): QuoteVersion {
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();
            $this->lifecycle->transition($quote, QuoteStatus::Sent);

            $options = $quote->options()->with('items')->get();
            foreach ($options as $option) {
                if ($option->items->isEmpty()) {
                    throw QuoteRuleViolation::emptyOption($option->label);
                }
            }

            $validUntil = $now->addHours($this->settings->quoteValidityHours());
            $quote->current_version++;
            $quote->valid_until = $validUntil;
            $quote->save();

            return QuoteVersion::query()->create([
                'quote_id' => $quote->id,
                'version' => $quote->current_version,
                'snapshot' => [
                    'currency' => $quote->sale_currency,
                    'options' => $options->map(fn(QuoteOption $option): array => $this->optionSnapshot($option, $quote->sale_currency))->values()->all(),
                ],
                'sent_at' => $now,
                'valid_until' => $validUntil,
                'sent_by' => $actor->id,
            ]);
        });

        (new QuoteSent($quote->ulid, $version->version))->publish();

        return $version;
    }

    /** @return array{ulid: string, label: string, title: string, sale_total_minor: int, items: list<array{description: string, product_type: string, service_date: string, nights: int, passenger_ages: list<int>, sale_amount_minor: int, ...}>} */
    private function optionSnapshot(QuoteOption $option, string $currency): array
    {
        return [
            'ulid' => $option->ulid,
            'label' => $option->label,
            'title' => $option->title,
            'sale_total_minor' => $option->saleTotal($currency)->getMinorAmount()->toInt(),
            'items' => array_values($option->items->map(static fn(QuoteItem $item): array => [
                'ulid' => $item->ulid,
                'kind' => $item->kind->value,
                'product_type' => $item->product_type->value,
                'description' => $item->description,
                'service_date' => $item->service_date->toDateString(),
                'nights' => $item->nights,
                'passenger_ages' => $item->passenger_ages,
                'net_amount_minor' => $item->net_amount_minor,
                'net_currency' => $item->net_currency,
                'sale_amount_minor' => $item->sale_amount_minor,
                'margin_amount_minor' => $item->margin_amount_minor,
                'price_breakdown' => $item->price_breakdown,
            ])->all()),
        ];
    }
}
