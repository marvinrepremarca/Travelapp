<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Livewire;

use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    public function mount(): void
    {
        Gate::authorize('create', Quote::class);
        $this->sale_currency = config()->string('travel.agency.default_currency');
        $this->sales_channel = (SalesChannel::tryFrom($this->sales_channel) ?? SalesChannel::Branch)->value;
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
            'customer_ulid' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'sale_currency' => ['required', 'string', 'size:3', 'alpha'],
            'sales_channel' => ['required', Rule::enum(SalesChannel::class)],
        ], attributes: $this->attributes());

        // Solo clientes dentro del alcance: uno ajeno se trata como inexistente.
        $customer = $this->visibleCustomers()->where('ulid', $validated['customer_ulid'])->first();
        if (! $customer instanceof Customer) {
            $this->addError('customer_ulid', __('quotes.errors.customer_not_found'));

            return;
        }

        $quote = $create->execute($this->actor(), $customer, $validated['title'], $validated['sale_currency'], SalesChannel::from($validated['sales_channel']));

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
            'matches' => $matches,
            'channels' => SalesChannel::cases(),
        ])->title(__('quotes.create'))->layoutData(['heading' => __('quotes.create')]);
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
