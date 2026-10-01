<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Livewire;

use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\CustomerLinkState;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Quotes\Services\ItineraryBuilder;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Vista del cliente (sin sesión) de una versión enviada: solo precios de venta, itinerario y aceptación.
 * Se abre únicamente desde el enlace firmado; las propiedades bloqueadas impiden cambiar de cotización o versión.
 */
#[Layout('components.layouts.portal')]
final class PublicQuote extends Component
{
    /** Solo viaja su identificador; Livewire la rehidrata una vez por request. */
    #[Locked]
    public Quote $quote;

    #[Locked]
    public int $version;

    public string $optionUlid = '';

    public string $acceptedBy = '';

    public bool $termsAccepted = false;

    /** Una versión inexistente responde 404 al renderizar. */
    public function mount(Quote $quote, int $version): void
    {
        $this->quote = $quote;
        $this->version = $version;
    }

    public function accept(AcceptQuoteAction $accept): void
    {
        $quote = $this->quote();
        $validated = $this->validate([
            'optionUlid' => ['required', 'string'],
            'acceptedBy' => ['required', 'string', 'max:150'],
            'termsAccepted' => ['accepted'],
        ], attributes: trans('quotes.public.fields'));

        if ($quote->current_version !== $this->version) {
            $this->addError('optionUlid', __('quotes.public.superseded'));

            return;
        }

        try {
            $accept->execute($quote, $validated['optionUlid'], AcceptanceChannel::CustomerLink, __('quotes.public.accepted_note', ['name' => $validated['acceptedBy']]), CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('optionUlid', $violation->getMessage());
        }

        // La acción trabaja sobre su propia copia bloqueada; se refleja el nuevo estado en pantalla.
        $this->quote->refresh();
    }

    public function render(MoneyPresenter $presenter, ItineraryBuilder $itinerary): View
    {
        $quote = $this->quote();
        $version = QuoteVersion::query()->where('quote_id', $quote->id)->where('version', $this->version)->firstOrFail();
        $currency = $version->snapshot['currency'];

        $options = array_map(static fn(array $option): array => [
            ...$option,
            'total' => Money::ofMinor($option['sale_total_minor'], $currency),
            'itinerary' => $itinerary->build($option['items']),
        ], $version->snapshot['options']);

        return view('quotes::livewire.public-quote', [
            'quote' => $quote,
            'sentVersion' => $version,
            'options' => $options,
            'currency' => $currency,
            'presenter' => $presenter,
            'state' => $this->state($quote),
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title($quote->title);
    }

    /** Qué puede hacer el cliente con esta versión. */
    private function state(Quote $quote): CustomerLinkState
    {
        return match (true) {
            $quote->status === QuoteStatus::Accepted && $quote->accepted_version === $this->version => CustomerLinkState::Accepted,
            $quote->current_version !== $this->version || $quote->status === QuoteStatus::Draft => CustomerLinkState::Superseded,
            $quote->status === QuoteStatus::Cancelled => CustomerLinkState::Cancelled,
            $quote->status === QuoteStatus::Expired || $quote->isPastValidity(CarbonImmutable::now()) => CustomerLinkState::Expired,
            default => CustomerLinkState::Open,
        };
    }

    private function quote(): Quote
    {
        return $this->quote;
    }
}
