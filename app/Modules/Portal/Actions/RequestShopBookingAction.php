<?php

declare(strict_types=1);

namespace App\Modules\Portal\Actions;

use App\Modules\Catalog\Contracts\ShopCatalog;
use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Crm\Data\LeadData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Portal\Data\ShopRequest;
use App\Modules\Portal\Exceptions\ShopRuleViolation;
use App\Modules\Portal\Services\ShopPricing;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;

/**
 * Convierte una solicitud de la tienda B2C en un lead para un asesor, con la salida, los cupos y el valor estimado
 * (precio "desde" por adulto recalculado en el servidor). El asesor confirma tarifas y cupos y envía la cotización.
 */
final readonly class RequestShopBookingAction
{
    public function __construct(
        private ShopCatalog $catalog,
        private LeadIntake $leads,
        private ShopPricing $pricing,
        private MoneyPresenter $presenter,
    ) {}

    /** @return string ULID del lead */
    public function execute(ShopRequest $request, CarbonImmutable $today): string
    {
        throw_unless($request->acceptsDataProcessing, ShopRuleViolation::consentRequired());

        $product = $this->catalog->product($request->productUlid, $today, $today->addDays(config()->integer('travel.portal.shop_window_days')));
        $departure = $product?->departure($request->departureUlid);
        throw_if(!$product instanceof \App\Modules\Catalog\Data\ShopProduct || !$departure instanceof \App\Modules\Catalog\Data\ScheduledDeparture, ShopRuleViolation::departureUnavailable());

        $available = $departure->capacity - $departure->reservedSeats;
        throw_if($request->seats > $available, ShopRuleViolation::notEnoughSeats($available));

        $estimate = $this->pricing->from($product, $departure->serviceDate, $request->seats);

        return $this->leads->register(new LeadData(
            contactName: $request->contactName,
            channel: SalesChannel::Online,
            email: $request->email,
            phone: $request->phone,
            destination: $departure->destinationCity,
            travelStart: $departure->serviceDate,
            travelEnd: $departure->serviceDate,
            travelersCount: $request->seats,
            notes: __('portal.shop.lead_notes', [
                'product' => $product->name,
                'departure' => $departure->startsAtLocal()->isoFormat('lll'),
                'seats' => $request->seats,
                'estimate' => $estimate instanceof \Brick\Money\Money ? $this->presenter->format($estimate) : __('portal.shop.no_estimate'),
                'policy' => config()->string('travel.privacy.policy_version'),
            ]),
        ), $this->advisor());
    }

    /** Asesor configurado ⚙ o, si no hay, el primer asesor activo. */
    private function advisor(): User
    {
        $email = config('travel.portal.shop_lead_owner_email');
        $query = User::query()->where('is_active', true);
        $advisor = is_string($email) && $email !== ''
            ? (clone $query)->where('email', $email)->first()
            : null;

        return $advisor
            ?? $query->role(Role::TravelAgent->value)->orderBy('id')->first()
            ?? throw ShopRuleViolation::noAdvisor();
    }
}
