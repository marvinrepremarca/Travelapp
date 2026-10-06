<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Cuentas bancarias de la agencia con sus movimientos por conciliar (solo finanzas). */
#[Layout('components.layouts.backoffice')]
final class BankAccountsIndex extends Component
{
    public function mount(): void
    {
        $this->authorizeFinance();
    }

    public function render(): View
    {
        return view('finance::livewire.bank-accounts-index', [
            'accounts' => AgencyBankAccount::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'pending' => BankStatementLine::query()
                ->where('status', StatementLineStatus::Pending)
                ->toBase()
                ->selectRaw('agency_bank_account_id, count(*) as aggregate')
                ->groupBy('agency_bank_account_id')
                ->pluck('aggregate', 'agency_bank_account_id')
                ->map(static fn(mixed $count): int => (int) $count)
                ->all(),
        ])->title(__('finance.bank_accounts.title'))
            ->layoutData(['heading' => __('finance.bank_accounts.title')]);
    }

    private function authorizeFinance(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->can(Permission::FinanceAccess->value), 403);
    }
}
