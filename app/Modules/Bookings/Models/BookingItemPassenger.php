<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Models;

use App\Modules\Customers\Models\Traveler;
use App\Modules\Shared\Enums\PassengerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Viajero del CRM asignado a un servicio del expediente.
 *
 * @property int $id
 * @property int $booking_item_id
 * @property int $traveler_id
 * @property int $age_at_service
 * @property PassengerType $passenger_type
 * @property-read Traveler $traveler
 */
final class BookingItemPassenger extends Model
{
    protected $fillable = ['booking_item_id', 'traveler_id', 'age_at_service', 'passenger_type'];

    /** @return BelongsTo<Traveler, $this> */
    public function traveler(): BelongsTo
    {
        return $this->belongsTo(Traveler::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['age_at_service' => 'integer', 'passenger_type' => PassengerType::class];
    }
}
