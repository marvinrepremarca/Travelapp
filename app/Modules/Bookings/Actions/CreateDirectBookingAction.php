<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Expediente creado directamente para un cliente, sin cotización previa (ADR-0007): Reservas funciona aunque
 * Cotizaciones esté apagada. Queda a nombre de quien lo crea y en su sucursal.
 */
final readonly class CreateDirectBookingAction
{
    public function execute(User $actor, Customer $customer, string $title, string $saleCurrency): Booking
    {
        return DB::transaction(static function () use ($actor, $customer, $title, $saleCurrency): Booking {
            $booking = new Booking([
                'customer_id' => $customer->id,
                'title' => $title,
                'sale_currency' => $saleCurrency,
            ]);
            $booking->owner_id = $actor->id;
            $booking->branch_id = $actor->branch_id;
            $booking->status = BookingStatus::InProgress;
            $booking->save();

            $booking->number = config()->string('travel.bookings.number_prefix')
                . str_pad((string) $booking->id, config()->integer('travel.bookings.number_digits'), '0', STR_PAD_LEFT);
            $booking->save();

            return $booking;
        });
    }
}
