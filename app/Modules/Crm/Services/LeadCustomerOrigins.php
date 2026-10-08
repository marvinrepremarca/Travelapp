<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Models\Lead;
use App\Modules\Customers\Contracts\CustomerOrigins;
use App\Modules\Customers\Data\CustomerOriginFollowUp;
use App\Modules\Customers\Data\CustomerPrefill;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;

/** Un prospecto de Comercial origina al cliente: prellena el formulario y queda vinculado al guardarlo. */
final readonly class LeadCustomerOrigins implements CustomerOrigins
{
    private const NAME_PARTS = 2;

    public function __construct(private Capabilities $capabilities) {}

    public function prefill(User $viewer, string $origin): ?CustomerPrefill
    {
        $lead = $this->openLead($viewer, $origin);
        if (! $lead instanceof Lead) {
            return null;
        }

        [$first, $last] = array_pad(explode(' ', trim($lead->contact_name), self::NAME_PARTS), self::NAME_PARTS, '');

        return new CustomerPrefill($first, $last, (string) $lead->phone, (string) $lead->email);
    }

    public function attach(User $viewer, string $origin, int $customerId): ?CustomerOriginFollowUp
    {
        $lead = $this->openLead($viewer, $origin);
        if (! $lead instanceof Lead) {
            return null;
        }

        $lead->customer_id = $customerId;
        $lead->save();

        return new CustomerOriginFollowUp($lead->quoteTitle(), $lead->channel);
    }

    /** Solo con Comercial encendida, y un prospecto visible para el usuario que aún no tiene cliente. */
    private function openLead(User $viewer, string $origin): ?Lead
    {
        if ($origin === '' || ! $this->capabilities->enabled(Capability::Commercial)) {
            return null;
        }

        return Lead::query()->visibleTo($viewer)->where('ulid', $origin)->whereNull('customer_id')->first();
    }
}
