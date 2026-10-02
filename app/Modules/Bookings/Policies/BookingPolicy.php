<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Policies;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

/** Expedientes por alcance del rol; fuera del alcance responde 404. */
final class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Booking $booking): Response
    {
        return $user->isActive() && $booking->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Booking $booking): Response
    {
        return $this->view($user, $booking);
    }
}
