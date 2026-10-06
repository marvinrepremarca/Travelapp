<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Actions\IgnoreStatementLineAction;
use App\Modules\Finance\Actions\ImportBankStatementAction;
use App\Modules\Finance\Actions\MatchStatementLineAction;
use App\Modules\Finance\Actions\ReopenStatementLineAction;
use App\Modules\Finance\Data\ReconciliationEntry;
use App\Modules\Finance\Enums\ReconciliationTarget;
use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Services\ReconciliationCandidates;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Conciliación bancaria: se carga el extracto CSV de una cuenta y cada movimiento del banco se cruza
 * con un abono, una liquidación a proveedor o una consignación de caja (sugeridos), o se ignora con nota.
 */
#[Layout('components.layouts.backoffice')]
final class ReconciliationScreen extends Component
{
    use WithFileUploads;
    use WithPagination;

    private const ENTRY_SEPARATOR = ':';

    #[Url(except: '')]
    public string $account = '';

    #[Url(except: 'pending')]
    public string $status = 'pending';

    /** @var UploadedFile|null */
    public $statementFile;

    /** @var array<string, string> cruce elegido por línea: "tipo:ulid" */
    public array $choices = [];

    /** @var array<string, string> nota para ignorar por línea */
    public array $notes = [];

    public function mount(): void
    {
        $this->authorizeFinance();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['account', 'status'], true)) {
            $this->resetPage();
            $this->reset('choices', 'notes');
        }
    }

    public function import(ImportBankStatementAction $import): void
    {
        $this->authorizeFinance();
        $this->validate(
            ['statementFile' => ['required', 'file', 'mimes:csv,txt', 'max:' . config()->integer('travel.finance.statement_max_kb')]],
            attributes: ['statementFile' => __('finance.reconciliation.file')],
        );
        $file = $this->statementFile;
        if (! $file instanceof UploadedFile) {
            return;
        }

        try {
            $statement = $import->execute($this->actor(), $this->selectedAccount(), $file->getClientOriginalName(), (string) $file->get(), CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('statementFile', $violation->getMessage());

            return;
        }

        $this->reset('statementFile');
        $this->resetPage();
        session()->flash('status', __('finance.reconciliation.imported', ['imported' => $statement->lines_imported, 'skipped' => $statement->lines_skipped]));
    }

    public function match(string $lineUlid, MatchStatementLineAction $match): void
    {
        $this->authorizeFinance();
        [$target, $entryUlid] = array_pad(explode(self::ENTRY_SEPARATOR, $this->choices[$lineUlid] ?? '', 2), 2, '');
        $targetCase = ReconciliationTarget::tryFrom($target);
        if (! $targetCase instanceof ReconciliationTarget || $entryUlid === '') {
            $this->addError("choices.{$lineUlid}", __('finance.reconciliation.choose_entry'));

            return;
        }

        $this->attempt("choices.{$lineUlid}", fn(): \App\Modules\Finance\Models\BankStatementLine => $match->execute($this->actor(), $lineUlid, $targetCase, $entryUlid, CarbonImmutable::now()));
    }

    public function ignore(string $lineUlid, IgnoreStatementLineAction $ignore): void
    {
        $this->authorizeFinance();
        $this->validate(["notes.{$lineUlid}" => ['required', 'string', 'max:500']], attributes: ["notes.{$lineUlid}" => __('finance.reconciliation.note')]);

        $this->attempt("notes.{$lineUlid}", fn(): \App\Modules\Finance\Models\BankStatementLine => $ignore->execute($this->actor(), $lineUlid, $this->notes[$lineUlid], CarbonImmutable::now()));
    }

    public function reopen(string $lineUlid, ReopenStatementLineAction $reopen): void
    {
        $this->authorizeFinance();
        $reopen->execute($this->actor(), $lineUlid, CarbonImmutable::now());
    }

    public function render(ReconciliationCandidates $candidates, MoneyPresenter $presenter): View
    {
        $accounts = AgencyBankAccount::query()->where('is_active', true)->orderBy('name')->get();
        $account = $accounts->firstWhere('ulid', $this->account) ?? $accounts->first();
        $status = StatementLineStatus::tryFrom($this->status);
        $data = ['accounts' => $accounts, 'selected' => $account, 'statuses' => StatementLineStatus::cases(), 'presenter' => $presenter];

        if (! $account instanceof AgencyBankAccount) {
            return $this->screen($data + ['lines' => null, 'counts' => [], 'suggestions' => [], 'options' => [], 'unmatched' => []]);
        }

        $lines = BankStatementLine::query()
            ->where('agency_bank_account_id', $account->id)
            ->when($status instanceof StatementLineStatus, static fn($query) => $query->where('status', $status))
            ->orderBy('posted_on')
            ->orderBy('id')
            ->paginate(config()->integer('travel.finance.per_page'));

        $range = BankStatementLine::query()->where('agency_bank_account_id', $account->id)->toBase()
            ->selectRaw('status, count(*) as aggregate, min(posted_on) as first_on, max(posted_on) as last_on')
            ->groupBy('status')->get();

        $entries = $range->isEmpty() ? [] : $candidates->unmatched(
            $account->currency,
            CarbonImmutable::parse((string) $range->min('first_on')),
            CarbonImmutable::parse((string) $range->max('last_on')),
        );

        // Por línea: primero las sugerencias (valor y fecha cercana), luego otros movimientos del mismo valor para corregir a mano.
        $suggestions = [];
        $options = [];
        foreach ($lines as $line) {
            if ($line->status !== StatementLineStatus::Pending) {
                continue;
            }

            $suggested = $candidates->suggestionsFor($line, $entries);
            $suggestions[$line->ulid] = array_map(static fn(ReconciliationEntry $entry): string => $entry->key(), $suggested);
            $sameAmount = array_filter($entries, static fn(ReconciliationEntry $entry): bool => $entry->amount->isEqualTo($line->amount()) && ! in_array($entry->key(), $suggestions[$line->ulid], true));
            $options[$line->ulid] = [...$suggested, ...array_values($sameAmount)];
            $this->choices[$line->ulid] ??= $suggestions[$line->ulid][0] ?? '';
        }

        return $this->screen($data + [
            'lines' => $lines,
            'counts' => $range->mapWithKeys(static fn(object $row): array => [(string) $row->status => (int) $row->aggregate])->all(),
            'suggestions' => $suggestions,
            'options' => $options,
            'unmatched' => array_slice($entries, 0, config()->integer('travel.finance.per_page')),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function screen(array $data): View
    {
        return view('finance::livewire.reconciliation', $data + ['separator' => self::ENTRY_SEPARATOR])
            ->title(__('finance.reconciliation.title'))
            ->layoutData(['heading' => __('finance.reconciliation.title')]);
    }

    private function selectedAccount(): AgencyBankAccount
    {
        $query = AgencyBankAccount::query()->where('is_active', true);

        return ($this->account === '' ? $query->orderBy('name') : $query->where('ulid', $this->account))->firstOrFail();
    }

    private function attempt(string $field, callable $operation): void
    {
        try {
            $operation();
            unset($this->choices[$this->lineOf($field)], $this->notes[$this->lineOf($field)]);
        } catch (BusinessRuleException $violation) {
            $this->addError($field, $violation->getMessage());
        }
    }

    private function lineOf(string $field): string
    {
        return substr($field, (int) strpos($field, '.') + 1);
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
