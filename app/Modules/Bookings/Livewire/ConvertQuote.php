<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\CreateBookingFromQuoteAction;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Contracts\AcceptedQuotes;
use App\Modules\Quotes\Data\AcceptedOption;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Shared\Support\VisibilityGuard;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Resumen de la opción aceptada y creación del expediente. Solo para cotizaciones dentro del alcance. */
#[Layout('components.layouts.backoffice')]
final class ConvertQuote extends Component
{
    #[Locked]
    public string $quoteUlid;

    public function mount(string $quote): void
    {
        $this->quoteUlid = $quote;
        $this->accepted();
    }

    public function convert(CreateBookingFromQuoteAction $create): void
    {
        $this->accepted();

        try {
            $booking = $create->execute($this->quoteUlid, CarbonImmutable::now());
        } catch (BookingRuleViolation $violation) {
            $this->addError('quote', $violation->getMessage());

            return;
        }

        session()->flash('status', __('bookings.created', ['number' => $booking->number]));
        $this->redirectRoute('bookings.show', $booking, navigate: true);
    }

    public function render(MoneyPresenter $presenter): View
    {
        $accepted = $this->accepted();
        $total = array_reduce($accepted->items, static fn(Money $carry, array $item): Money => $carry->plus(Money::ofMinor($item['sale_amount_minor'], $accepted->saleCurrency)), Money::zero($accepted->saleCurrency));

        return view('bookings::livewire.convert-quote', [
            'accepted' => $accepted,
            // Ya convertida: se muestra el enlace al expediente en lugar del botón.
            'existing' => Booking::query()->where('quote_ulid', $this->quoteUlid)->first(['id', 'ulid', 'number']),
            'total' => $total,
            'presenter' => $presenter,
        ])->title(__('bookings.convert.title'))
            ->layoutData(['heading' => __('bookings.convert.title')]);
    }

    /** Cotización aceptada y visible para el usuario; si no, 404 (no se revela su existencia). */
    private function accepted(): AcceptedOption
    {
        try {
            $accepted = app(AcceptedQuotes::class)->acceptedOption($this->quoteUlid);
        } catch (QuoteRuleViolation) {
            abort(404);
        }

        /** @var User $user */
        $user = Auth::user();
        abort_unless(VisibilityGuard::allows($user, $accepted->ownerId, $accepted->branchId), 404);

        return $accepted;
    }
}
