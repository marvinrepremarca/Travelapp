<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
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
    #[Locked]
    public Booking $booking;

    /** Servicio que se está gestionando en el panel de acciones. */
    public string $itemUlid = '';

    /** @var array<string, string> */
    public array $action = ['status' => '', 'confirmation' => '', 'departure' => '', 'note' => ''];

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

    public function render(MoneyPresenter $presenter, AppSettings $settings, CatalogInventory $inventory): View
    {
        $booking = $this->booking->load(['customer:id,ulid,display_name', 'items']);
        $managed = $this->itemUlid === '' ? null : $booking->items->firstWhere('ulid', $this->itemUlid);

        return view('bookings::livewire.booking-show', [
            'booking' => $booking,
            'managed' => $managed,
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
