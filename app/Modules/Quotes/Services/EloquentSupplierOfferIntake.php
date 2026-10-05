<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Contracts\SupplierOfferIntake;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;

final readonly class EloquentSupplierOfferIntake implements SupplierOfferIntake
{
    private const DRAFTS_LIMIT = 20;

    private const LABEL_SEPARATOR = ' · ';

    public function __construct(private AddItemAction $addItem) {}

    public function draftsFor(User $actor): array
    {
        return Quote::query()
            ->visibleTo($actor)
            ->where('status', QuoteStatus::Draft)
            ->latest('id')
            ->limit(self::DRAFTS_LIMIT)
            ->get(['id', 'ulid', 'number', 'title'])
            ->mapWithKeys(static fn(Quote $quote): array => [$quote->ulid => $quote->number . self::LABEL_SEPARATOR . $quote->title])
            ->all();
    }

    public function add(string $quoteUlid, User $actor, QuoteItemData $data): void
    {
        $quote = Quote::query()->visibleTo($actor)->where('ulid', $quoteUlid)->first();
        if (! $quote instanceof Quote) {
            throw QuoteRuleViolation::draftNotFound();
        }

        $this->addItem->execute($quote, $quote->options()->firstOrFail(), $data);
    }
}
