<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Contracts\QuoteMetrics;
use App\Modules\Quotes\Data\ExpiringQuote;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentQuoteMetrics implements QuoteMetrics
{
    private const FIRST_VERSION = 1;

    public function firstSentBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return $this->visible($viewer, $ownerId)
            ->whereIn('id', QuoteVersion::query()
                ->select('quote_id')
                ->where('version', self::FIRST_VERSION)
                ->where('sent_at', '>=', $from)
                ->where('sent_at', '<', $until))
            ->count();
    }

    public function acceptedBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return $this->visible($viewer, $ownerId)->where('accepted_at', '>=', $from)->where('accepted_at', '<', $until)->count();
    }

    public function expiringFor(int $ownerId, CarbonImmutable $now, CarbonImmutable $until, int $limit): array
    {
        return array_values(Quote::query()
            ->where('owner_id', $ownerId)
            ->where('status', QuoteStatus::Sent)
            ->whereBetween('valid_until', [$now, $until])
            ->with('customer:id,display_name')
            ->orderBy('valid_until')
            ->limit($limit)
            ->get(['id', 'ulid', 'number', 'customer_id', 'valid_until'])
            ->map(static fn(Quote $quote): ExpiringQuote => new ExpiringQuote(
                $quote->ulid,
                (string) $quote->number,
                $quote->customer->display_name,
                $quote->valid_until ?? $until,
            ))
            ->all());
    }

    /** @return Builder<Quote> */
    private function visible(ScopedViewer $viewer, ?int $ownerId): Builder
    {
        return Quote::query()->visibleTo($viewer)->when($ownerId !== null, static fn(Builder $query) => $query->where('owner_id', $ownerId));
    }
}
