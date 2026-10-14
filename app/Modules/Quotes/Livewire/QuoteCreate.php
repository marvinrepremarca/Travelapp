<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Livewire;

use App\Modules\Crm\Contracts\LeadProspects;
use App\Modules\Crm\Data\LeadProspect;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Nueva cotización: se elige un cliente visible para el usuario, el título, la moneda y el canal. */
#[Layout('components.layouts.backoffice')]
final class QuoteCreate extends Component
{
    #[Url(as: 'customer', except: '')]
    public string $customer_ulid = '';

    public string $customerSearch = '';

    #[Url(except: '')]
    public string $title = '';

    public string $sale_currency = '';

    #[Url(as: 'channel', except: '')]
    public string $sales_channel = '';

    /** Prospecto desde el que se cotiza: al enviar la cotización, Comercial lo marca cotizado. */
    #[Url(as: 'lead', except: '')]
    public string $lead_ulid = '';

    public function mount(): void
    {
        Gate::authorize('create', Quote::class);
        $this->sales_channel = SalesChannel::tryFrom($this->sales_channel)->value ?? '';

        // Desde un lead: se precargan sus datos; si ya tiene cliente se usa ese, si no la cotización va al prospecto.
        $prospect = $this->prospect();
        if ($prospect instanceof LeadProspect) {
            $this->customer_ulid = $this->customer_ulid !== '' ? $this->customer_ulid : (string) $prospect->customerUlid;
            $this->title = $this->title !== '' ? $this->title : $prospect->quoteTitle;
            $this->sales_channel = $this->sales_channel !== '' ? $this->sales_channel : $prospect->channel->value;
        }
    }

    /** Quita el cliente elegido: la cotización vuelve a dirigirse al prospecto del lead. */
    public function useProspect(): void
    {
        $this->customer_ulid = '';
    }

    public function chooseCustomer(string $ulid): void
    {
        $this->customer_ulid = $ulid;
        $this->customerSearch = '';
    }

    public function save(CreateQuoteAction $create): void
    {
        Gate::authorize('create', Quote::class);
        $validated = $this->validate([
            'customer_ulid' => [$this->prospect() instanceof LeadProspect ? 'nullable' : 'required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'sale_currency' => ['required', 'string', 'size:3', 'alpha'],
            'sales_channel' => ['required', Rule::enum(SalesChannel::class)],
        ], attributes: $this->attributes());

        // Solo clientes dentro del alcance: uno ajeno se trata como inexistente.
        $customer = ($validated['customer_ulid'] ?? '') === '' ? null : $this->visibleCustomers()->where('ulid', $validated['customer_ulid'])->first();
        if (($validated['customer_ulid'] ?? '') !== '' && ! $customer instanceof Customer) {
            $this->addError('customer_ulid', __('quotes.errors.customer_not_found'));

            return;
        }

        $prospect = $this->prospect();
        $quote = $create->execute($this->actor(), $customer, $validated['title'], $validated['sale_currency'], SalesChannel::from($validated['sales_channel']), $prospect?->leadUlid, $prospect);

        session()->flash('status', __('quotes.created', ['number' => $quote->number]));
        $this->redirectRoute('quotes.show', $quote, navigate: true);
    }

    public function render(): View
    {
        $selected = $this->customer_ulid === '' ? null : $this->visibleCustomers()->where('ulid', $this->customer_ulid)->first(['id', 'ulid', 'display_name']);
        $matches = $this->customerSearch === '' ? collect() : $this->visibleCustomers()
            ->where('display_name', 'like', '%' . $this->customerSearch . '%')
            ->orderBy('display_name')
            ->limit(config()->integer('travel.quotes.per_page'))
            ->get(['id', 'ulid', 'display_name']);

        return view('quotes::livewire.quote-create', [
            'selected' => $selected,
            'prospect' => $this->prospect(),
            'matches' => $matches,
            'channels' => SalesChannel::cases(),
        ])->title(__('quotes.create'))->layoutData(['heading' => __('quotes.create')]);
    }

    /** Lead abierto y visible desde el que se cotiza; memorizado por request. */
    private function prospect(): ?LeadProspect
    {
        if (! Str::isUlid($this->lead_ulid)) {
            return null;
        }

        return once(fn(): ?LeadProspect => app(LeadProspects::class)->prospect($this->actor(), mb_strtolower($this->lead_ulid)));
    }

    /** @return Builder<Customer> */
    private function visibleCustomers(): Builder
    {
        return Customer::query()->visibleTo($this->actor());
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('quotes.fields');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
