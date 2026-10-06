<?php

declare(strict_types=1);

namespace App\Modules\Finance\Livewire;

use App\Modules\Finance\Actions\SaveBankAccountAction;
use App\Modules\Finance\Data\BankAccountData;
use App\Modules\Finance\Data\StatementFormat;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatement;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Brick\Money\Currency;
use Brick\Money\Exception\UnknownCurrencyException;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Crear o editar una cuenta bancaria de la agencia y el formato de su extracto CSV (solo finanzas). */
#[Layout('components.layouts.backoffice')]
final class BankAccountForm extends Component
{
    /** Separadores de columna que entiende el lector de CSV. */
    public const DELIMITERS = [',', ';', '|', "\t"];

    public const DECIMAL_SEPARATORS = ['.', ','];

    /** Formatos de fecha de los extractos más comunes. */
    public const DATE_FORMATS = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y', 'Ymd', 'Y/m/d'];

    private const MAX_HEADER_ROW = 20;

    #[Locked]
    public ?string $accountUlid = null;

    #[Locked]
    public bool $currencyLocked = false;

    public string $name = '';

    public string $bank_name = '';

    public string $account_last_digits = '';

    public string $currency = '';

    public bool $is_active = true;

    /** @var array<string, string> */
    public array $format = [];

    public function mount(?AgencyBankAccount $account = null): void
    {
        $this->authorizeFinance();
        $format = $account?->exists ? $account->format() : StatementFormat::fromArray([]);
        $this->format = array_map(static fn(string|int|null $value): string => (string) $value, $format->toArray());

        if (! $account?->exists) {
            $this->currency = config()->string('travel.agency.default_currency');

            return;
        }

        $this->accountUlid = $account->ulid;
        $this->currencyLocked = BankStatement::query()->where('agency_bank_account_id', $account->id)->exists();
        $this->fill([
            'name' => $account->name,
            'bank_name' => $account->bank_name,
            'account_last_digits' => $account->account_last_digits,
            'currency' => $account->currency,
            'is_active' => $account->is_active,
        ]);
    }

    public function save(SaveBankAccountAction $save): void
    {
        $this->authorizeFinance();
        $this->currency = mb_strtoupper(trim($this->currency));
        $validated = $this->validate();

        $saved = $save->execute(new BankAccountData(
            name: $validated['name'],
            bankName: $validated['bank_name'],
            accountLastDigits: $validated['account_last_digits'],
            currency: $validated['currency'],
            isActive: (bool) $validated['is_active'],
            format: StatementFormat::fromArray($validated['format']),
        ), $this->account());

        session()->flash('status', __('finance.bank_accounts.saved', ['name' => $saved->name]));
        $this->redirectRoute('finance.bank-accounts', navigate: true);
    }

    public function render(): View
    {
        $title = $this->accountUlid === null ? __('finance.bank_accounts.create') : __('finance.bank_accounts.edit');

        return view('finance::livewire.bank-account-form', [
            'delimiters' => collect(self::DELIMITERS)->mapWithKeys(static fn(string $delimiter): array => [$delimiter => __('finance.bank_accounts.delimiters.' . bin2hex($delimiter))])->all(),
            'decimalSeparators' => collect(self::DECIMAL_SEPARATORS)->mapWithKeys(static fn(string $separator): array => [$separator => __('finance.bank_accounts.decimal_separators.' . bin2hex($separator))])->all(),
            'dateFormats' => collect(self::DATE_FORMATS)->mapWithKeys(static fn(string $format): array => [$format => __('finance.bank_accounts.date_example', ['example' => now()->format($format)])])->all(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'account_last_digits' => ['required', 'digits:4'],
            'currency' => ['required', 'size:3', $this->isoCurrency(...)],
            'is_active' => ['boolean'],
            'format.delimiter' => ['required', Rule::in(self::DELIMITERS)],
            'format.date_format' => ['required', Rule::in(self::DATE_FORMATS)],
            'format.decimal_separator' => ['required', Rule::in(self::DECIMAL_SEPARATORS)],
            'format.header_row' => ['required', 'integer', 'min:1', 'max:' . self::MAX_HEADER_ROW],
            'format.date_column' => ['required', 'string', 'max:60'],
            'format.description_column' => ['required', 'string', 'max:60'],
            'format.reference_column' => ['nullable', 'string', 'max:60'],
            'format.amount_column' => ['nullable', 'required_without_all:format.debit_column,format.credit_column', 'string', 'max:60'],
            'format.debit_column' => ['nullable', 'required_with:format.credit_column', 'required_without:format.amount_column', 'string', 'max:60'],
            'format.credit_column' => ['nullable', 'required_with:format.debit_column', 'required_without:format.amount_column', 'string', 'max:60'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        /** @var array<string, string> $fields */
        $fields = trans('finance.bank_accounts.fields');

        return collect($fields)->mapWithKeys(static fn(string $label, string $field): array => [str_starts_with($field, 'format_') ? 'format.' . substr($field, strlen('format_')) : $field => $label])->all();
    }

    private function isoCurrency(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Currency::of((string) $value);
        } catch (UnknownCurrencyException) {
            $fail(__('finance.bank_accounts.invalid_currency'));
        }
    }

    private function account(): ?AgencyBankAccount
    {
        return $this->accountUlid === null ? null : AgencyBankAccount::query()->where('ulid', $this->accountUlid)->firstOrFail();
    }

    private function authorizeFinance(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->can(Permission::FinanceAccess->value), 403);
    }
}
