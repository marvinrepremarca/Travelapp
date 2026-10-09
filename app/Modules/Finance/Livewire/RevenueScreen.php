<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Actions\RecordManualRevenueAction;
use App\Modules\Finance\Data\ManualRevenueData;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Finance\Models\RevenueEntry;
use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Contracts\InvoicingMetrics;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Payments\Contracts\CollectionTotals;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ingresos contables (base de causación, ADR-0007): los reconocidos al confirmar servicios y los registrados a
 * mano, conciliados contra lo cobrado (Cobros) y lo facturado (Facturación) del mismo mes.
 */
#[Layout('components.layouts.backoffice')]
final class RevenueScreen extends Component
{
    use WithPagination;

    private const MONTH_FORMAT = 'Y-m';

    private const MONTH_PATTERN = '/^\d{4}-(0[1-9]|1[0-2])$/';

    #[Url(except: '')]
    public string $month = '';

    #[Url(except: '')]
    public string $source = '';

    /** @var array{description: string, customer: string, amount: string, date: string} */
    public array $manual = ['description' => '', 'customer' => '', 'amount' => '', 'date' => ''];

    public function mount(): void
    {
        $this->authorizeFinance();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['month', 'source'], true)) {
            $this->resetPage();
        }
    }

    public function record(RecordManualRevenueAction $record, MoneyPresenter $presenter): void
    {
        $this->authorizeFinance();
        $validated = $this->validate([
            'manual.description' => ['required', 'string', 'max:255'],
            'manual.customer' => ['nullable', 'string', 'max:255'],
            'manual.amount' => ['required', 'numeric', 'gt:0'],
            'manual.date' => ['required', 'date'],
        ], attributes: Arr::dot(trans('finance.revenue.fields')));

        try {
            $entry = $record->execute($this->actor(), new ManualRevenueData(
                $validated['manual']['description'],
                $validated['manual']['customer'] ?: null,
                Money::of($validated['manual']['amount'], $this->currency()),
                CarbonImmutable::parse($validated['manual']['date']),
            ), $this->today());
        } catch (BusinessRuleException $violation) {
            $this->addError('manual.amount', $violation->getMessage());

            return;
        }

        session()->flash('status', __('finance.revenue.recorded', ['amount' => $presenter->format($entry->amount())]));
        $this->reset('manual');
    }

    public function render(MoneyPresenter $presenter, CollectionTotals $collections, InvoicingMetrics $invoicing): View
    {
        $from = $this->monthStart();
        $until = $from->addMonth();
        $currency = $this->currency();
        $source = RevenueSource::tryFrom($this->source);

        $entries = RevenueEntry::query()
            ->visibleTo($this->actor())
            ->where('recognized_on', '>=', $from->toDateString())
            ->where('recognized_on', '<', $until->toDateString())
            ->when($source instanceof RevenueSource, static fn($query) => $query->where('source', $source))
            ->orderByDesc('recognized_on')
            ->orderByDesc('id')
            ->paginate(config()->integer('travel.finance.per_page'));

        $recognized = Money::ofMinor((int) RevenueEntry::query()
            ->visibleTo($this->actor())
            ->where('currency', $currency)
            ->where('recognized_on', '>=', $from->toDateString())
            ->where('recognized_on', '<', $until->toDateString())
            ->sum('amount_minor'), $currency);

        $agencyFrom = $from->shiftTimezone(config()->string('travel.agency.timezone'))->utc();
        $agencyUntil = $until->shiftTimezone(config()->string('travel.agency.timezone'))->utc();
        $issued = $invoicing->issuedTotals($agencyFrom, $agencyUntil, $currency);

        $title = __('finance.revenue.title');

        return view('finance::livewire.revenue', [
            'entries' => $entries,
            'recognized' => $recognized,
            'collected' => $collections->netCollectedBetween($currency, $agencyFrom, $agencyUntil),
            'invoiced' => ($issued[InvoiceType::Invoice->value] ?? Money::zero($currency))
                ->plus($issued[InvoiceType::DebitNote->value] ?? Money::zero($currency))
                ->minus($issued[InvoiceType::CreditNote->value] ?? Money::zero($currency)),
            'sources' => RevenueSource::cases(),
            'currency' => $currency,
            'presenter' => $presenter,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    private function monthStart(): CarbonImmutable
    {
        $month = preg_match(self::MONTH_PATTERN, $this->month) === 1 ? $this->month : $this->today()->format(self::MONTH_FORMAT);

        return CarbonImmutable::createFromFormat(self::MONTH_FORMAT, $month)?->startOfMonth() ?? $this->today()->startOfMonth();
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse(CarbonImmutable::now(config()->string('travel.agency.timezone'))->toDateString());
    }

    private function currency(): string
    {
        return config()->string('travel.agency.default_currency');
    }

    private function authorizeFinance(): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
