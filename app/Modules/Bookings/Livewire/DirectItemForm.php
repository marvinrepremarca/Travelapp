<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\AddDirectItemAction;
use App\Modules\Bookings\Data\DirectItemData;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Agrega servicios a un expediente directo; el precio de venta lo calcula el servidor (ADR-0007). */
#[Layout('components.layouts.backoffice')]
final class DirectItemForm extends Component
{
    private const AGES_SEPARATOR = ',';

    #[Locked]
    public string $bookingUlid = '';

    /** @var array{description: string, product_type: string, supplier: string, destination_country: string, service_date: string, nights: string, ages: string, net_amount: string, net_currency: string, channel: string} */
    public array $item = [
        'description' => '', 'product_type' => '', 'supplier' => '', 'destination_country' => '', 'service_date' => '',
        'nights' => '0', 'ages' => '', 'net_amount' => '', 'net_currency' => '', 'channel' => '',
    ];

    public function mount(Booking $booking): void
    {
        Gate::authorize('update', $booking);
        $this->bookingUlid = $booking->ulid;
        $this->item['net_currency'] = $booking->sale_currency;
        $this->item['channel'] = SalesChannel::Branch->value;
    }

    public function add(AddDirectItemAction $add): void
    {
        $booking = $this->booking();
        Gate::authorize('update', $booking);
        $validated = $this->validate([
            'item.description' => ['required', 'string', 'max:255'],
            'item.product_type' => ['required', Rule::enum(ProductType::class)],
            'item.supplier' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'item.destination_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'item.service_date' => ['required', 'date'],
            'item.nights' => ['required', 'integer', 'min:0'],
            'item.ages' => ['required', 'string', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/'],
            'item.net_amount' => ['required', 'numeric', 'gt:0'],
            'item.net_currency' => ['required', 'string', 'size:3', 'alpha'],
            'item.channel' => ['required', Rule::enum(SalesChannel::class)],
        ], attributes: Arr::dot(trans('bookings.direct.item_fields')));
        $data = $validated['item'];

        try {
            $add->execute($booking, new DirectItemData(
                description: $data['description'],
                productType: ProductType::from($data['product_type']),
                supplierId: $data['supplier'] !== '' && $data['supplier'] !== null ? (int) $data['supplier'] : null,
                destinationCountry: $data['destination_country'] ? mb_strtoupper($data['destination_country']) : null,
                serviceDate: CarbonImmutable::parse($data['service_date']),
                nights: (int) $data['nights'],
                passengerAges: array_values(array_map(intval(...), array_map(trim(...), explode(self::AGES_SEPARATOR, $data['ages'])))),
                supplierNet: Money::of($data['net_amount'], mb_strtoupper($data['net_currency'])),
                channel: SalesChannel::from($data['channel']),
            ), CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('item.net_amount', $violation->getMessage());

            return;
        }

        session()->flash('status', __('bookings.direct.item_added', ['service' => $data['description']]));
        $this->redirectRoute('bookings.show', $booking, navigate: true);
    }

    public function render(): View
    {
        $booking = $this->booking();
        $title = __('bookings.direct.add_item_title', ['number' => $booking->number]);

        return view('bookings::livewire.direct-item-form', [
            'booking' => $booking,
            'productTypes' => ProductType::cases(),
            'channels' => SalesChannel::cases(),
            'suppliers' => Supplier::query()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    private function booking(): Booking
    {
        return Booking::query()->where('ulid', $this->bookingUlid)->firstOrFail();
    }
}
