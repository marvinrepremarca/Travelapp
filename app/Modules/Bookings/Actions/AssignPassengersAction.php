<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Models\BookingItemPassenger;
use App\Modules\Crm\Models\Traveler;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\PassengerType;
use Illuminate\Support\Facades\DB;

/**
 * Asigna viajeros del cliente a un servicio. Deben ser tantos como los cotizados y del mismo tipo por edad
 * a la fecha del servicio (adulto, niño, infante), porque el precio se calculó con esa composición.
 */
final class AssignPassengersAction
{
    /**
     * @param  list<string>  $travelerUlids
     */
    public function execute(BookingItem $item, array $travelerUlids): void
    {
        if ($item->status->isClosed()) {
            throw BookingRuleViolation::itemClosed();
        }

        $booking = $item->booking()->firstOrFail();
        $travelers = Traveler::query()->where('customer_id', $booking->customer_id)->whereIn('ulid', $travelerUlids)->get();
        if ($travelers->count() !== count(array_unique($travelerUlids))) {
            throw BookingRuleViolation::travelerNotOfCustomer();
        }

        $assigned = $travelers->map(static fn(Traveler $traveler): array => [
            'traveler_id' => $traveler->id,
            'age_at_service' => $traveler->ageAt($item->service_date),
            'passenger_type' => $traveler->passengerTypeAt($item->service_date),
        ]);

        $this->assertMatchesQuotedComposition($item, array_values($assigned->map(static fn(array $passenger): PassengerType => $passenger['passenger_type'])->all()));

        DB::transaction(static function () use ($item, $assigned): void {
            BookingItemPassenger::query()->where('booking_item_id', $item->id)->delete();
            foreach ($assigned as $passenger) {
                BookingItemPassenger::query()->create(['booking_item_id' => $item->id, ...$passenger]);
            }

            activity(AuditLogName::Bookings->value)
                ->performedOn($item)
                ->withProperties(['traveler_ids' => $assigned->pluck('traveler_id')->all()])
                ->log('passengers_assigned');
        });
    }

    /** @param list<PassengerType> $assignedTypes */
    private function assertMatchesQuotedComposition(BookingItem $item, array $assignedTypes): void
    {
        $quoted = array_count_values(array_map(static fn(int $age): string => PassengerType::forAge($age)->value, $item->passenger_ages));
        $assigned = array_count_values(array_map(static fn(PassengerType $type): string => $type->value, $assignedTypes));
        ksort($quoted);
        ksort($assigned);

        if ($quoted !== $assigned) {
            throw BookingRuleViolation::passengersDoNotMatch($this->describe($quoted), $this->describe($assigned));
        }
    }

    /** @param array<string, int> $counts */
    private function describe(array $counts): string
    {
        return collect($counts)->map(static fn(int $count, string $type): string => $count . ' ' . PassengerType::from($type)->label())->implode(', ') ?: '0';
    }
}
