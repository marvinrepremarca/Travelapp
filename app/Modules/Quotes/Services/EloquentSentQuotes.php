<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Contracts\SentQuotes;
use App\Modules\Quotes\Data\QuoteShare;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use Carbon\CarbonImmutable;

final readonly class EloquentSentQuotes implements SentQuotes
{
    public function __construct(private QuoteLinks $links) {}

    public function share(string $quoteUlid): ?QuoteShare
    {
        $quote = Quote::query()->where('ulid', $quoteUlid)->first();
        if (! $quote instanceof Quote || $quote->status !== QuoteStatus::Sent || ! $quote->valid_until instanceof CarbonImmutable || $quote->isPastValidity(CarbonImmutable::now())) {
            return null;
        }

        $url = $this->links->customerUrl($quote);

        return $url === null ? null : new QuoteShare($quote->ulid, (string) $quote->number, $quote->title, $quote->customer_id, $quote->owner_id, $quote->branch_id, $url, $quote->valid_until);
    }
}
