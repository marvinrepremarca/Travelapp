<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Pricing\Actions\FetchOfficialRateAction;
use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use App\Modules\Pricing\Models\ExchangeRate;
use App\Modules\Shared\Enums\Permission;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Tasas de cambio: consulta para todos; carga manual y descarga de la TRM para finanzas. */
#[Layout('components.layouts.backoffice')]
final class ExchangeRatesManager extends Component
{
    use WithPagination;

    public string $base = 'USD';

    public string $quote = 'COP';

    public string $rate = '';

    public string $valid_on = '';

    public function mount(): void
    {
        $this->valid_on = CarbonImmutable::today()->toDateString();
    }

    public function record(RecordExchangeRateAction $record): void
    {
        Gate::authorize(Permission::FinanceAccess->value);

        $validated = $this->validate([
            'base' => ['required', 'string', 'size:3', 'alpha', 'different:quote'],
            'quote' => ['required', 'string', 'size:3', 'alpha'],
            'rate' => ['required', 'numeric', 'gt:0', 'decimal:0,8'],
            'valid_on' => ['required', 'date'],
        ], attributes: $this->attributes());

        $record->execute(
            $validated['base'],
            $validated['quote'],
            BigDecimal::of((string) $validated['rate']),
            ExchangeRateSource::Manual,
            CarbonImmutable::parse($validated['valid_on']),
            $this->actor()->id,
        );

        $this->reset('rate');
        session()->flash('status', __('pricing.rates.saved'));
    }

    public function fetchOfficial(FetchOfficialRateAction $fetch): void
    {
        Gate::authorize(Permission::FinanceAccess->value);

        try {
            $fetch->execute(CarbonImmutable::parse($this->valid_on));
        } catch (ExchangeRateUnavailable $exception) {
            $this->addError('rate', $exception->getMessage());

            return;
        }

        session()->flash('status', __('pricing.rates.fetched'));
    }

    public function render(): View
    {
        return view('pricing::livewire.exchange-rates-manager', [
            'rates' => ExchangeRate::query()->latest('valid_on')->latest('id')->paginate(config()->integer('travel.pricing.per_page')),
            'canManage' => $this->actor()->can(Permission::FinanceAccess->value),
        ])->title(__('pricing.rates.title'))
            ->layoutData(['heading' => __('pricing.rates.title')]);
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('pricing.rates.fields');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
