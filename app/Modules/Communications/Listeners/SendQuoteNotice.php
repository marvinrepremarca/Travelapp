<?php

declare(strict_types=1);

namespace App\Modules\Communications\Listeners;

use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Services\CustomerNotifier;
use App\Modules\Quotes\Contracts\SentQuotes;
use App\Modules\Quotes\Data\QuoteShare;
use App\Modules\Quotes\Events\QuoteSent;

/** Cotización enviada → el cliente recibe por WhatsApp el enlace para verla y aceptarla. */
final readonly class SendQuoteNotice
{
    public function __construct(
        private SentQuotes $quotes,
        private CustomerNotifier $notifier,
    ) {}

    public function handle(QuoteSent $event): void
    {
        $share = $this->quotes->share($event->quoteUlid);
        if (! $share instanceof QuoteShare) {
            return;
        }

        $this->notifier->notify($share->customerId, $share->ownerId, $share->branchId, NoticeTemplate::QuoteSent, [
            'number' => $share->number,
            'title' => $share->title,
            'url' => $share->url,
            'valid_until' => $share->validUntil->setTimezone(config()->string('travel.agency.timezone'))->translatedFormat(config()->string('travel.communications.notice_datetime_format')),
        ], NoticeTemplate::QuoteSent->value . ':' . $share->quoteUlid . ':' . $event->version);
    }
}
