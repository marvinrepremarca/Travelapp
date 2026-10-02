<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\AssignPassengersAction;
use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\SetCancellationPolicyAction;
use App\Modules\Bookings\Data\CancellationPolicy;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Models\BookingItemPassenger;
use App\Modules\Bookings\Services\CancellationPenaltyCalculator;
use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Crm\Models\Traveler;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Shared\ValueObjects\Percentage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Ficha del expediente: servicios con su estado ante el proveedor, confirmación con código y cupos de producto propio. */
#[Layout('components.layouts.backoffice')]
final class BookingShow extends Component
{
    /** Formato de tramos en el formulario: "30:0, 15:50, 7:100" (días:porcentaje). */
    private const TIERS_SEPARATOR = ',';

    private const TIER_SEPARATOR = ':';

    private const MAX_PERCENT = '100';

    #[Locked]
    public Booking $booking;

    /** Servicio que se está gestionando en el panel de acciones. */
    public string $itemUlid = '';

    /** @var array<string, string> */
    public array $action = ['status' => '', 'confirmation' => '', 'departure' => '', 'note' => ''];

    /** Servicio al que se le están asignando pasajeros. */
    public string $passengerItemUlid = '';

    /** @var list<string> */
    public array $selectedTravelers = [];

    /** Servicio cuya política de cancelación se edita. */
    public string $policyItemUlid = '';

    /** @var array{non_refundable: bool, tiers: string} */
    public array $policy = ['non_refundable' => false, 'tiers' => ''];

    public function mount(Booking $booking): void
    {
        Gate::authorize('view', $booking);
        $this->booking = $booking;
    }

    public function manage(string $itemUlid): void
    {
        $this->item($itemUlid);
        $this->itemUlid = $itemUlid;
        $this->reset('action');
        $this->resetErrorBag();
    }

    public function apply(ConfirmItemAction $confirm, ChangeItemStatusAction $change): void
    {
        Gate::authorize('update', $this->booking);
        $item = $this->item($this->itemUlid);
        $isConfirm = $this->action['status'] === BookingItemStatus::Confirmed->value;

        $data = $this->validate([
            'action.status' => ['required', Rule::in(array_map(static fn(BookingItemStatus $status): string => $status->value, $item->status->allowedTransitions()))],
            'action.confirmation' => [$isConfirm ? 'required' : 'nullable', 'string', 'max:100'],
            'action.departure' => [$isConfirm && $item->isOwnProduct() ? 'required' : 'nullable', 'string'],
            'action.note' => [$isConfirm ? 'nullable' : 'required', 'string', 'max:2000'],
        ], attributes: $this->prefixed())['action'];

        try {
            $isConfirm
                ? $confirm->execute($item, (string) $data['confirmation'], $data['departure'] ?: null, CarbonImmutable::now())
                : $change->execute($item, BookingItemStatus::from($data['status']), (string) $data['note'], CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('action.status', $violation->getMessage());

            return;
        }

        $this->booking->refresh();
        $this->reset('itemUlid', 'action');
    }

    public function editPassengers(string $itemUlid): void
    {
        $item = $this->item($itemUlid);
        $this->passengerItemUlid = $itemUlid;
        $this->selectedTravelers = array_values($item->passengers()->with('traveler:id,ulid')->get()->map(static fn(BookingItemPassenger $passenger): string => $passenger->traveler->ulid)->all());
        $this->resetErrorBag();
    }

    public function savePassengers(AssignPassengersAction $assign): void
    {
        Gate::authorize('update', $this->booking);
        $item = $this->item($this->passengerItemUlid);
        $this->validate(['selectedTravelers' => ['array'], 'selectedTravelers.*' => ['string']]);

        try {
            $assign->execute($item, $this->selectedTravelers);
        } catch (BusinessRuleException $violation) {
            $this->addError('selectedTravelers', $violation->getMessage());

            return;
        }

        $this->reset('passengerItemUlid', 'selectedTravelers');
    }

    public function editPolicy(string $itemUlid): void
    {
        $item = $this->item($itemUlid);
        $current = CancellationPolicy::fromArray($item->cancellation_policy ?? []);
        $this->policyItemUlid = $itemUlid;
        $this->policy = [
            'non_refundable' => $current->nonRefundable,
            'tiers' => collect($current->tiers)->map(static fn(array $tier): string => $tier['days_before'] . self::TIER_SEPARATOR . Percentage::fromBasisPoints($tier['rate_basis_points'])->toPercentString())->implode(self::TIERS_SEPARATOR . ' '),
        ];
        $this->resetErrorBag();
    }

    public function savePolicy(SetCancellationPolicyAction $set): void
    {
        Gate::authorize('update', $this->booking);
        $item = $this->item($this->policyItemUlid);
        $data = $this->validate([
            'policy.non_refundable' => ['boolean'],
            'policy.tiers' => [$this->policy['non_refundable'] ? 'nullable' : 'required', 'string', 'regex:/^\s*\d{1,3}\s*:\s*\d{1,3}(\.\d{1,2})?\s*(,\s*\d{1,3}\s*:\s*\d{1,3}(\.\d{1,2})?\s*)*$/'],
        ], attributes: ['policy.tiers' => __('bookings.policy.tiers'), 'policy.non_refundable' => __('bookings.policy.non_refundable')])['policy'];

        $tiers = [];
        foreach (array_filter(explode(self::TIERS_SEPARATOR, (string) $data['tiers']), static fn(string $tier): bool => trim($tier) !== '') as $tier) {
            [$days, $rate] = array_map(trim(...), explode(self::TIER_SEPARATOR, $tier));
            $percentage = Percentage::fromString($rate);
            if ($percentage->basisPoints > Percentage::fromString(self::MAX_PERCENT)->basisPoints) {
                $this->addError('policy.tiers', __('bookings.policy.max_rate'));

                return;
            }
            $tiers[] = ['days_before' => (int) $days, 'rate_basis_points' => $percentage->basisPoints];
        }

        try {
            $set->execute($item, new CancellationPolicy((bool) $data['non_refundable'], $tiers));
        } catch (BusinessRuleException $violation) {
            $this->addError('policy.tiers', $violation->getMessage());

            return;
        }

        $this->reset('policyItemUlid', 'policy');
    }

    public function render(MoneyPresenter $presenter, AppSettings $settings, CatalogInventory $inventory, CancellationPenaltyCalculator $penalties): View
    {
        $booking = $this->booking->load(['customer:id,ulid,display_name', 'items.passengers.traveler:id,first_name,last_name']);
        $managed = $this->itemUlid === '' ? null : $booking->items->firstWhere('ulid', $this->itemUlid);
        $passengerItem = $this->passengerItemUlid === '' ? null : $booking->items->firstWhere('ulid', $this->passengerItemUlid);

        return view('bookings::livewire.booking-show', [
            'booking' => $booking,
            'managed' => $managed,
            'passengerItem' => $passengerItem,
            'policyItem' => $this->policyItemUlid === '' ? null : $booking->items->firstWhere('ulid', $this->policyItemUlid),
            'penalties' => $booking->items->mapWithKeys(fn(BookingItem $item): array => [$item->ulid => $penalties->penaltyFor($item, CarbonImmutable::now())]),
            'travelers' => $passengerItem instanceof BookingItem
                ? Traveler::query()->where('customer_id', $booking->customer_id)->orderBy('first_name')->get(['id', 'ulid', 'customer_id', 'first_name', 'last_name', 'birth_date'])
                : collect(),
            'missingPassengers' => $booking->items->filter(static fn(BookingItem $item): bool => ! $item->status->isClosed() && $item->passengers->isEmpty())->count(),
            'departures' => $managed instanceof BookingItem && $managed->isOwnProduct() ? $inventory->departuresOn((string) $managed->catalog_product_ulid, $managed->service_date) : [],
            'presenter' => $presenter,
            'canSeeMargin' => $this->actor()->can(Permission::MarginsView->value) || ! $settings->hideMarginsFromAgents(),
        ])->title($booking->number . ' · ' . $booking->title)
            ->layoutData(['heading' => $booking->number . ' · ' . $booking->title]);
    }

    private function item(string $ulid): BookingItem
    {
        return BookingItem::query()->where('booking_id', $this->booking->id)->where('ulid', $ulid)->first() ?? abort(404);
    }

    /** @return array<string, string> */
    private function prefixed(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('bookings.action_fields');

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["action.{$field}" => $label])->all();
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
