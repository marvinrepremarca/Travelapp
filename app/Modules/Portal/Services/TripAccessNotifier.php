<?php

declare(strict_types=1);

namespace App\Modules\Portal\Services;

use App\Modules\Bookings\Data\Trip;
use App\Modules\Communications\Contracts\CustomerNotices;
use App\Modules\Communications\Enums\NoticeTemplate;

/** Envía al titular, por WhatsApp, su enlace personal a "Mi viaje". */
final readonly class TripAccessNotifier
{
    public function __construct(
        private TripLinks $links,
        private CustomerNotices $notices,
    ) {}

    public function send(Trip $trip, string $dedupeKey): bool
    {
        $expiresAt = $this->links->expiresAt();

        return $this->notices->send($trip->customerId, $trip->ownerId, $trip->branchId, NoticeTemplate::TripPortal, [
            'number' => $trip->number,
            'title' => $trip->title,
            'url' => $this->links->trip($trip->ulid, $expiresAt),
            'expires' => $expiresAt->setTimezone(config()->string('travel.agency.timezone'))->translatedFormat(config()->string('travel.communications.notice_datetime_format')),
        ], $dedupeKey);
    }
}
