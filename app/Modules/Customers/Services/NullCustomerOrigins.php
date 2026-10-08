<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use App\Modules\Customers\Contracts\CustomerOrigins;
use App\Modules\Customers\Data\CustomerOriginFollowUp;
use App\Modules\Customers\Data\CustomerPrefill;
use App\Modules\Identity\Models\User;

/** Sin capacidad que origine clientes: el cliente se registra a mano. */
final class NullCustomerOrigins implements CustomerOrigins
{
    public function prefill(User $viewer, string $origin): ?CustomerPrefill
    {
        return null;
    }

    public function attach(User $viewer, string $origin, int $customerId): ?CustomerOriginFollowUp
    {
        return null;
    }
}
