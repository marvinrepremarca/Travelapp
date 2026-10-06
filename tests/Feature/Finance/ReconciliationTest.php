<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Finance\Actions\IgnoreStatementLineAction;
use App\Modules\Finance\Actions\ImportBankStatementAction;
use App\Modules\Finance\Actions\MatchStatementLineAction;
use App\Modules\Finance\Actions\RecordCashExpenseAction;
use App\Modules\Finance\Actions\ReopenStatementLineAction;
use App\Modules\Finance\Actions\SaveBankAccountAction;
use App\Modules\Finance\Actions\SettleSupplierPayablesAction;
use App\Modules\Finance\Data\BankAccountData;
use App\Modules\Finance\Data\StatementFormat;
use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\ReconciliationTarget;
use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Livewire\BankAccountForm;
use App\Modules\Finance\Livewire\BankAccountsIndex;
use App\Modules\Finance\Livewire\ReconciliationScreen;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Finance\Services\ReconciliationCandidates;
use App\Modules\Finance\Services\StatementCsvParser;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Actions\ValidateTransferAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Formato de un banco colombiano típico: punto y coma, fecha d/m/Y, coma decimal y columna de valor con signo. */
function colombianFormat(array $overrides = []): StatementFormat
{
    return StatementFormat::fromArray([
        'delimiter' => ';',
        'date_format' => 'd/m/Y',
        'decimal_separator' => ',',
        'header_row' => 1,
        'date_column' => 'Fecha',
        'description_column' => 'Descripción',
        'reference_column' => 'Referencia',
        'amount_column' => 'Valor',
        ...$overrides,
    ]);
}

function agencyAccount(?StatementFormat $format = null, string $currency = 'COP'): AgencyBankAccount
{
    return app(SaveBankAccountAction::class)->execute(new BankAccountData('Corriente principal', 'Banco Demo', '1234', $currency, true, $format ?? colombianFormat()));
}

function importCsv(AgencyBankAccount $account, string $csv, string $name = 'extracto.csv'): App\Modules\Finance\Models\BankStatement
{
    return app(ImportBankStatementAction::class)->execute(financeUser(), $account, $name, $csv, CarbonImmutable::now());
}

/** Transferencia de un cliente aprobada por finanzas el 2026-10-01. */
function approvedTransfer(string $amount, string $reference): Payment
{
    $booking = familyBooking(agent());
    $receiver = User::query()->findOrFail($booking->owner_id);
    $payment = app(RecordPaymentAction::class)->execute($receiver, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::BankTransfer, Money::of($amount, 'COP'), $reference, null, CarbonImmutable::now());

    return app(ValidateTransferAction::class)->execute(financeUser(), $payment, true, null, CarbonImmutable::now());
}

it('reads statements with signed amounts, thousands separators, parentheses and BOM', function (): void {
    $csv = "\xEF\xBB\xBFFecha;DESCRIPCION;Referencia;Valor\n02/10/2026;Transferencia recibida;TRX-9;1.250.000,50\n03/10/2026;Comisión;;(15.000)\n\n04/10/2026;Pago proveedor;LIQ-1;-300.000\n";

    $lines = app(StatementCsvParser::class)->parse($csv, colombianFormat(), 'COP');

    expect($lines)->toHaveCount(3)
        ->and((string) $lines[0]->amount->getAmount())->toBe('1250000.50')
        ->and($lines[0]->reference)->toBe('TRX-9')
        ->and($lines[0]->postedOn->toDateString())->toBe('2026-10-02')
        ->and((string) $lines[1]->amount->getAmount())->toBe('-15000.00')
        ->and($lines[1]->reference)->toBeNull()
        ->and((string) $lines[2]->amount->getAmount())->toBe('-300000.00');
});

it('reads statements with debit and credit columns', function (): void {
    $format = StatementFormat::fromArray(['delimiter' => ',', 'date_format' => 'Y-m-d', 'decimal_separator' => '.', 'header_row' => 2, 'date_column' => 'Date', 'description_column' => 'Detail', 'debit_column' => 'Debit', 'credit_column' => 'Credit']);
    $csv = "Banco Demo - Cuenta 1234\nDate,Detail,Debit,Credit\n2026-10-02,Deposit,,100.25\n2026-10-03,Fee,5,\n";

    $lines = app(StatementCsvParser::class)->parse($csv, $format, 'USD');

    expect((string) $lines[0]->amount->getAmount())->toBe('100.25')
        ->and((string) $lines[1]->amount->getAmount())->toBe('-5.00');
});

it('fails closed on unreadable statements', function (string $csv, string $key, array $replace): void {
    expect(fn() => app(StatementCsvParser::class)->parse($csv, colombianFormat(), 'COP'))->toThrow(FinanceRuleViolation::class, __($key, $replace));
})->with([
    'missing column' => ["Fecha;Detalle;Valor\n02/10/2026;x;1\n", 'finance.errors.statement_columns_missing', ['columns' => 'Descripción, Referencia']],
    'bad date' => ["Fecha;Descripción;Referencia;Valor\n2026-10-02;x;;1\n", 'finance.errors.statement_row_invalid', ['row' => 2]],
    'bad amount' => ["Fecha;Descripción;Referencia;Valor\n02/10/2026;x;;abc\n", 'finance.errors.statement_row_invalid', ['row' => 2]],
    'only header' => ["Fecha;Descripción;Referencia;Valor\n", 'finance.errors.statement_empty', []],
]);

it('imports a statement once and skips lines repeated by overlapping statements', function (): void {
    $account = agencyAccount();
    $first = "Fecha;Descripción;Referencia;Valor\n02/10/2026;Abono;A1;100\n02/10/2026;Abono;A1;100\n03/10/2026;GMF;;-4\n";
    $overlap = "Fecha;Descripción;Referencia;Valor\n03/10/2026;GMF;;-4\n04/10/2026;Abono;A2;50\n";

    $statement = importCsv($account, $first);

    expect($statement->lines_imported)->toBe(3)
        ->and(fn(): \App\Modules\Finance\Models\BankStatement => importCsv($account, $first, 'otra-copia.csv'))->toThrow(FinanceRuleViolation::class, __('finance.errors.statement_already_imported'));

    $second = importCsv($account, $overlap);
    expect($second->lines_imported)->toBe(1)
        ->and($second->lines_skipped)->toBe(1)
        ->and(BankStatementLine::query()->count())->toBe(4)
        ->and(BankStatementLine::query()->where('status', StatementLineStatus::Pending)->count())->toBe(4);
});

it('refuses statements for inactive accounts and keeps bank data immutable', function (): void {
    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n02/10/2026;Abono;A1;100\n");
    $line = BankStatementLine::query()->sole();

    expect(fn() => $line->update(['amount_minor' => 1]))->toThrow(LogicException::class)
        ->and(fn() => $line->delete())->toThrow(LogicException::class);

    $account->update(['is_active' => false]);
    expect(fn(): \App\Modules\Finance\Models\BankStatement => importCsv($account, "Fecha;Descripción;Referencia;Valor\n05/10/2026;x;;1\n"))->toThrow(FinanceRuleViolation::class, __('finance.errors.bank_account_inactive'));
});

it('suggests the customer transfer with the same value, nearby date and reference', function (): void {
    $payment = approvedTransfer('250000', 'TRX-778');
    $other = approvedTransfer('250000', 'OTRA');
    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n03/10/2026;Transferencia TRX-778;;250.000\n20/10/2026;Fuera de ventana;;250.000\n");
    [$near, $far] = BankStatementLine::query()->orderBy('posted_on')->get()->all();
    $candidates = app(ReconciliationCandidates::class);
    $entries = $candidates->unmatched('COP', $near->posted_on, $far->posted_on);

    $suggestions = $candidates->suggestionsFor($near, $entries);

    expect(array_map(static fn(\App\Modules\Finance\Data\ReconciliationEntry $entry): string => $entry->ulid, $suggestions))->toBe([$payment->ulid, $other->ulid])
        ->and($candidates->suggestionsFor($far, $entries))->toBe([]);
});

it('matches each system movement with only one bank line', function (): void {
    $payment = approvedTransfer('250000', 'TRX-1');
    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n02/10/2026;Abono;TRX-1;250.000\n02/10/2026;Abono duplicado;;250.000\n03/10/2026;Otro valor;;100\n");
    [$first, $second, $third] = BankStatementLine::query()->orderBy('id')->get()->all();
    $match = app(MatchStatementLineAction::class);

    $matched = $match->execute(financeUser(), $first->ulid, ReconciliationTarget::CustomerPayment, $payment->ulid, CarbonImmutable::now());

    expect($matched->status)->toBe(StatementLineStatus::Matched)
        ->and($matched->matched_ulid)->toBe($payment->ulid)
        ->and(fn() => $match->execute(financeUser(), $second->ulid, ReconciliationTarget::CustomerPayment, $payment->ulid, CarbonImmutable::now()))->toThrow(FinanceRuleViolation::class, __('finance.errors.match_not_valid'))
        ->and(fn() => $match->execute(financeUser(), $third->ulid, ReconciliationTarget::CustomerPayment, $payment->ulid, CarbonImmutable::now()))->toThrow(FinanceRuleViolation::class)
        ->and(fn() => $match->execute(financeUser(), $first->ulid, ReconciliationTarget::CustomerPayment, $payment->ulid, CarbonImmutable::now()))->toThrow(FinanceRuleViolation::class, __('finance.errors.line_not_pending'))
        ->and(app(ReconciliationCandidates::class)->unmatched('COP', CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-05')))->toBe([]);
});

it('reconciles supplier settlements and cash deposits as bank outflows and inflows', function (): void {
    $supplier = Supplier::factory()->create(['payment_terms' => PaymentTerms::Credit, 'payment_days' => 30]);
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    $hotel->supplier_id = $supplier->id;
    $hotel->save();
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
    app(SettleSupplierPayablesAction::class)->execute(financeUser(), [SupplierPayable::query()->sole()->ulid], 'LIQ-55', CarbonImmutable::now());
    $settlement = (string) SupplierPayable::query()->sole()->settlement_ulid;

    $cashier = User::query()->findOrFail($booking->owner_id);
    $deposit = app(RecordCashExpenseAction::class)->execute($cashier, app(CashDesk::class)->openSessionFor((int) $cashier->branch_id) ?? throw new RuntimeException(), Money::of('80000', 'COP'), 'Consignación 001', CashMovementType::BankDeposit);

    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n02/10/2026;Pago LIQ-55;;-500.000\n01/10/2026;Consignación efectivo;;80.000\n");
    $lines = BankStatementLine::query()->orderBy('posted_on')->get();
    $entries = app(ReconciliationCandidates::class)->unmatched('COP', CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-02'));

    expect(app(ReconciliationCandidates::class)->suggestionsFor($lines[0], $entries)[0]->ulid)->toBe($deposit->ulid)
        ->and(app(ReconciliationCandidates::class)->suggestionsFor($lines[1], $entries)[0]->ulid)->toBe($settlement)
        ->and(app(MatchStatementLineAction::class)->execute(financeUser(), $lines[1]->ulid, ReconciliationTarget::SupplierSettlement, $settlement, CarbonImmutable::now())->status)->toBe(StatementLineStatus::Matched)
        ->and(app(MatchStatementLineAction::class)->execute(financeUser(), $lines[0]->ulid, ReconciliationTarget::CashDeposit, $deposit->ulid, CarbonImmutable::now())->status)->toBe(StatementLineStatus::Matched)
        ->and(app(ReconciliationCandidates::class)->find(ReconciliationTarget::CashDeposit, 'no-existe'))->toBeNull();
});

it('subtracts bank deposits from the expected cash and accepts only outflows', function (): void {
    $agent = agent();
    openCashFor($agent);
    $session = app(CashDesk::class)->openSessionFor((int) $agent->branch_id) ?? throw new RuntimeException();
    $before = app(CashDesk::class)->expected($session);

    app(RecordCashExpenseAction::class)->execute($agent, $session, Money::of('1000', 'COP'), 'Consignación', CashMovementType::BankDeposit);

    expect((string) $before->minus(app(CashDesk::class)->expected($session))->getAmount())->toBe('1000.00')
        ->and(fn() => app(RecordCashExpenseAction::class)->execute($agent, $session, Money::of('1', 'COP'), 'x', CashMovementType::Income))->toThrow(InvalidArgumentException::class);
});

it('ignores bank charges with a note and reopens a line', function (): void {
    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n02/10/2026;GMF 4x1000;;-4.000\n");
    $line = BankStatementLine::query()->sole();

    $ignored = app(IgnoreStatementLineAction::class)->execute(financeUser(), $line->ulid, 'Impuesto bancario', CarbonImmutable::now());
    $reopened = app(ReopenStatementLineAction::class)->execute(financeUser(), $line->ulid, CarbonImmutable::now());

    expect($ignored->status)->toBe(StatementLineStatus::Ignored)
        ->and($ignored->note)->toBe('Impuesto bancario')
        ->and($reopened->status)->toBe(StatementLineStatus::Pending)
        ->and($reopened->note)->toBeNull();
});

it('creates and edits bank accounts with validated forms', function (): void {
    actingAs(financeUser());

    Livewire::test(BankAccountForm::class)
        ->assertSet('currency', 'COP')
        ->set('account_last_digits', '12')
        ->set('currency', 'XYZ')
        ->set('format.amount_column', '')
        ->call('save')
        ->assertHasErrors(['name', 'bank_name', 'account_last_digits', 'currency', 'format.date_column', 'format.amount_column'])
        ->set('name', 'Corriente principal')
        ->set('bank_name', 'Banco Demo')
        ->set('account_last_digits', '9876')
        ->set('currency', 'cop')
        ->set('format.delimiter', ';')
        ->set('format.date_column', 'Fecha')
        ->set('format.description_column', 'Descripción')
        ->set('format.amount_column', 'Valor')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('finance.bank-accounts'));

    $account = AgencyBankAccount::query()->sole();
    expect($account->currency)->toBe('COP')->and($account->format()->delimiter)->toBe(';');

    importCsv($account, "Fecha;Descripción;Valor\n02/10/2026;Abono;100\n");
    Livewire::test(BankAccountForm::class, ['account' => $account])
        ->assertSet('currencyLocked', true)
        ->set('name', 'Corriente renombrada')
        ->set('currency', 'USD')
        ->call('save')
        ->assertHasNoErrors();
    expect($account->fresh()?->name)->toBe('Corriente renombrada')->and($account->fresh()?->currency)->toBe('COP');

    Livewire::test(BankAccountsIndex::class)->assertSee('Corriente renombrada')->assertSee(trans_choice('finance.bank_accounts.pending_count', 1, ['count' => 1]));
});

it('reconciles from the screen: upload, confirm suggestion, ignore and undo', function (): void {
    $payment = approvedTransfer('250000', 'TRX-5');
    $account = agencyAccount();
    actingAs(financeUser());
    $csv = "Fecha;Descripción;Referencia;Valor\n02/10/2026;Transferencia;TRX-5;250.000\n02/10/2026;Comisión;;-9.000\n";

    $screen = Livewire::test(ReconciliationScreen::class)
        ->assertSee(__('finance.reconciliation.empty'))
        ->call('import')
        ->assertHasErrors('statementFile')
        ->set('statementFile', UploadedFile::fake()->createWithContent('extracto.csv', $csv))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSee(__('finance.reconciliation.imported', ['imported' => 2, 'skipped' => 0]))
        ->assertSee(__('finance.reconciliation.suggested'))
        ->assertSee(__('finance.reconciliation.no_candidates'));
    [$transfer, $fee] = BankStatementLine::query()->orderBy('id')->get()->all();

    $screen->assertSet("choices.{$transfer->ulid}", ReconciliationTarget::CustomerPayment->value . ':' . $payment->ulid)
        ->call('match', $transfer->ulid)
        ->assertHasNoErrors()
        ->call('match', $fee->ulid)
        ->assertHasErrors("choices.{$fee->ulid}")
        ->call('ignore', $fee->ulid)
        ->assertHasErrors("notes.{$fee->ulid}")
        ->set("notes.{$fee->ulid}", 'Comisión del banco')
        ->call('ignore', $fee->ulid)
        ->assertHasNoErrors()
        ->assertSee(__('finance.reconciliation.empty'))
        ->assertSee(__('finance.reconciliation.all_matched'))
        ->set('status', StatementLineStatus::Matched->value)
        ->assertSee(__('finance.reconciliation.matched_with', ['target' => ReconciliationTarget::CustomerPayment->label()]))
        ->call('reopen', $transfer->ulid)
        ->set('status', StatementLineStatus::Pending->value)
        ->assertSee('Transferencia')
        ->set('statementFile', UploadedFile::fake()->createWithContent('extracto.csv', $csv))
        ->call('import')
        ->assertHasErrors('statementFile');

    expect($transfer->fresh()?->status)->toBe(StatementLineStatus::Pending);
});

it('reconciles a line with a stale choice gracefully', function (): void {
    $account = agencyAccount();
    importCsv($account, "Fecha;Descripción;Referencia;Valor\n02/10/2026;Abono;;100\n");
    $line = BankStatementLine::query()->sole();
    actingAs(financeUser());

    Livewire::test(ReconciliationScreen::class)
        ->set("choices.{$line->ulid}", ReconciliationTarget::CustomerPayment->value . ':01JZZZZZZZZZZZZZZZZZZZZZZZ')
        ->call('match', $line->ulid)
        ->assertHasErrors("choices.{$line->ulid}");
});

it('shows bank screens only to finance and guides when there are no accounts', function (): void {
    actingAs(agent())->get(route('finance.reconciliation'))->assertForbidden();
    actingAs(agent())->get(route('finance.bank-accounts'))->assertForbidden();
    actingAs(agent())->get(route('finance.bank-accounts.create'))->assertForbidden();

    actingAs(financeUser());
    Livewire::test(ReconciliationScreen::class)->assertSee(__('finance.reconciliation.no_accounts'));
    Livewire::test(BankAccountsIndex::class)->assertSee(__('finance.bank_accounts.empty_title'));
});

it('labels reconciliation enums', function (): void {
    foreach ([...StatementLineStatus::cases(), ...ReconciliationTarget::cases(), ...CashMovementType::cases()] as $case) {
        expect($case->label())->not->toStartWith('finance.');
    }

    expect(ReconciliationTarget::SupplierSettlement->isInflow())->toBeFalse();
});
