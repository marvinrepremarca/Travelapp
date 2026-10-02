<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Data\CancellationPolicy;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;

/** Define la política de cancelación pactada para un servicio vigente. Queda en la auditoría. */
final class SetCancellationPolicyAction
{
    public function execute(BookingItem $item, CancellationPolicy $policy): BookingItem
    {
        if ($item->status->isClosed()) {
            throw BookingRuleViolation::itemClosed();
        }

        $item->cancellation_policy = $policy->toArray();
        $item->save();

        return $item;
    }
}
