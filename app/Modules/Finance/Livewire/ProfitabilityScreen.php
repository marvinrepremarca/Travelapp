<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Services\ProfitabilityCalculator;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\VisibilityScope;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Rentabilidad por expediente del mes de venta, con totales por asesor y por sucursal (quien ve márgenes). */
#[Layout('components.layouts.backoffice')]
final class ProfitabilityScreen extends Component
{
    use WithPagination;

    private const MONTH_FORMAT = 'Y-m';

    private const PERIOD_FORMAT = 'F Y';

    private const MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    #[Url(except: '')]
    public string $month = '';

    #[Url(except: '')]
    public string $branch = '';

    public function mount(): void
    {
        $this->authorizeMargins();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['month', 'branch'], true)) {
            $this->resetPage();
        }
    }

    public function render(ProfitabilityCalculator $calculator, MoneyPresenter $presenter): View
    {
        $timezone = config()->string('travel.agency.timezone');
        $start = $this->monthStart($timezone);
        $canChooseBranch = $this->actor()->visibilityScope() === VisibilityScope::All;
        $branchId = $canChooseBranch && ctype_digit($this->branch) ? (int) $this->branch : null;

        $report = $calculator->report($this->actor(), $start->utc(), $start->addMonth()->utc(), $branchId);
        $perPage = config()->integer('travel.finance.per_page');
        $page = max(1, $this->getPage());
        $bookings = new LengthAwarePaginator(
            array_slice($report->bookings, ($page - 1) * $perPage, $perPage),
            count($report->bookings),
            $perPage,
            $page,
        );

        return view('finance::livewire.profitability', [
            'report' => $report,
            'bookings' => $bookings,
            'owners' => User::query()->whereIn('id', array_keys($report->byOwner))->orderBy('name')->pluck('name', 'id')->all(),
            'branches' => Branch::query()->orderBy('name')->pluck('name', 'id')->all(),
            'canChooseBranch' => $canChooseBranch,
            'periodLabel' => $start->translatedFormat(self::PERIOD_FORMAT),
            'timezone' => $timezone,
            'presenter' => $presenter,
        ])->title(__('finance.profitability.title'))
            ->layoutData(['heading' => __('finance.profitability.title')]);
    }

    /** Primer instante del mes elegido en la zona de la agencia; mes actual si el valor no es válido. */
    private function monthStart(string $timezone): CarbonImmutable
    {
        $fallback = CarbonImmutable::now($timezone)->startOfMonth();
        if (preg_match(self::MONTH_PATTERN, $this->month) !== 1) {
            return $fallback;
        }

        $parsed = CarbonImmutable::createFromFormat(self::MONTH_FORMAT . '-d', $this->month . '-01', $timezone);

        return $parsed instanceof CarbonImmutable && $parsed->format(self::MONTH_FORMAT) === $this->month ? $parsed->startOfDay() : $fallback;
    }

    private function authorizeMargins(): void
    {
        abort_unless($this->actor()->can(Permission::MarginsView->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
