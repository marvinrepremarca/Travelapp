<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Actions\CloseCashSessionAction;
use App\Modules\Finance\Actions\OpenCashSessionAction;
use App\Modules\Finance\Actions\RecordCashExpenseAction;
use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\VisibilityScope;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Caja diaria de la sucursal: apertura con base, entradas por abonos en efectivo, salidas y cierre con arqueo.
 * Cada usuario opera la caja de su sucursal; quien tiene alcance total puede elegir la sucursal.
 */
#[Layout('components.layouts.backoffice')]
final class CashRegisterScreen extends Component
{
    #[Url(except: '')]
    public string $branch = '';

    /** @var array<string, string> */
    public array $form = ['opening' => '', 'expense_amount' => '', 'expense_description' => '', 'counted' => '', 'note' => ''];

    public function mount(): void
    {
        $this->branchId();
    }

    public function open(OpenCashSessionAction $open): void
    {
        $data = $this->validate(['form.opening' => ['required', 'numeric', 'min:0', 'decimal:0,2']], attributes: ['form.opening' => __('finance.cash.opening')])['form'];

        $this->attempt('form.opening', fn(): \App\Modules\Finance\Models\CashSession => $open->execute($this->actor(), $this->branchId(), $this->money((string) $data['opening']), CarbonImmutable::now()));
    }

    public function expense(RecordCashExpenseAction $record, CashDesk $desk): void
    {
        $data = $this->validate([
            'form.expense_amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'form.expense_description' => ['required', 'string', 'max:255'],
        ], attributes: ['form.expense_amount' => __('finance.cash.amount'), 'form.expense_description' => __('finance.cash.description')])['form'];

        $session = $desk->openSessionFor($this->branchId()) ?? throw FinanceRuleViolation::cashSessionClosed();
        $this->attempt('form.expense_amount', fn(): \App\Modules\Finance\Models\CashMovement => $record->execute($this->actor(), $session, $this->money((string) $data['expense_amount']), (string) $data['expense_description']));
    }

    public function close(CloseCashSessionAction $close, CashDesk $desk): void
    {
        $data = $this->validate([
            'form.counted' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'form.note' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['form.counted' => __('finance.cash.counted')])['form'];

        $session = $desk->openSessionFor($this->branchId());
        if (! $session instanceof CashSession) {
            $this->addError('form.counted', __('finance.errors.cash_session_closed'));

            return;
        }

        $this->attempt('form.counted', fn(): \App\Modules\Finance\Models\CashSession => $close->execute($this->actor(), $session, $this->money((string) $data['counted']), $data['note'] ?: null, CarbonImmutable::now()));
    }

    public function render(CashDesk $desk, MoneyPresenter $presenter): View
    {
        $branchId = $this->branchId();
        $session = $desk->openSessionFor($branchId)?->load('movements');

        return view('finance::livewire.cash-register', [
            'session' => $session,
            'expected' => $session instanceof CashSession ? $desk->expected($session) : null,
            'history' => CashSession::query()->where('branch_id', $branchId)->where('status', CashSessionStatus::Closed)->latest('closed_at')->limit(config()->integer('travel.finance.cash_history_size'))->get(),
            'branches' => $this->canChooseBranch() ? Branch::query()->orderBy('name')->pluck('name', 'id')->all() : [],
            'currency' => config()->string('travel.agency.default_currency'),
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('finance.cash.title'))
            ->layoutData(['heading' => __('finance.cash.title')]);
    }

    /** Sucursal de la caja: la propia; con alcance total, la elegida (o la propia si no elige). */
    private function branchId(): int
    {
        $own = $this->actor()->branch_id;
        if ($this->canChooseBranch() && $this->branch !== '' && Branch::query()->whereKey((int) $this->branch)->exists()) {
            return (int) $this->branch;
        }

        abort_if($own === null, 403, __('finance.errors.user_without_branch'));

        return $own;
    }

    private function canChooseBranch(): bool
    {
        return $this->actor()->visibilityScope() === VisibilityScope::All;
    }

    private function money(string $amount): Money
    {
        return Money::of($amount, config()->string('travel.agency.default_currency'));
    }

    private function attempt(string $field, callable $operation): void
    {
        try {
            $operation();
            $this->reset('form');
        } catch (BusinessRuleException $violation) {
            $this->addError($field, $violation->getMessage());
        }
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
