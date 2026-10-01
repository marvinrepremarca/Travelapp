<?php

declare(strict_types=1);

use App\Modules\Audit\Models\SensitiveDataAccess;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Actions\AddBankAccountAction;
use App\Modules\Suppliers\Actions\AddCommissionAction;
use App\Modules\Suppliers\Actions\EndCommissionAction;
use App\Modules\Suppliers\Contracts\SupplierDirectory;
use App\Modules\Suppliers\Enums\BankAccountType;
use App\Modules\Suppliers\Enums\CommissionBase;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Enums\SupplierStanding;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Livewire\SupplierForm;
use App\Modules\Suppliers\Livewire\SupplierShow;
use App\Modules\Suppliers\Livewire\SuppliersIndex;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function supplierManager(): App\Modules\Identity\Models\User
{
    return userWithRole(Role::ProductManager);
}

function addCommission(Supplier $supplier, string $from, ?string $until = null, ProductType $type = ProductType::Hotel, string $rate = '10'): App\Modules\Suppliers\Models\SupplierCommission
{
    return app(AddCommissionAction::class)->execute(
        $supplier,
        $type,
        Percentage::fromString($rate),
        CommissionBase::Gross,
        CarbonImmutable::parse($from),
        $until === null ? null : CarbonImmutable::parse($until),
        supplierManager(),
    );
}

it('requires authentication and lets every internal user browse suppliers', function (): void {
    get(route('suppliers.index'))->assertRedirect(route('login'));

    $supplier = Supplier::factory()->create();
    actingAs(agent())->get(route('suppliers.index'))->assertOk()->assertSee($supplier->trade_name)->assertDontSee(__('suppliers.create'));
    actingAs(agent())->get(route('suppliers.show', $supplier))->assertOk();
});

it('forbids managing suppliers without permission', function (): void {
    actingAs(agent())->get(route('suppliers.create'))->assertForbidden();
});

it('creates a colombian tourism supplier only with RNT', function (): void {
    actingAs(supplierManager());

    $form = Livewire::test(SupplierForm::class)
        ->set('legal_name', 'Hotel del Mar S.A.S.')
        ->set('trade_name', 'Hotel del Mar')
        ->set('tax_id', '900.111.222')
        ->set('payment_terms', PaymentTerms::Prepaid->value)
        ->set('payment_days', '7')
        ->call('save')
        ->assertHasErrors(['rnt_number' => __('suppliers.errors.rnt_required')]);

    $form->set('rnt_number', '12345')->set('rnt_expires_on', now()->addYear()->toDateString())->call('save')->assertHasNoErrors();

    $supplier = Supplier::query()->sole();
    expect($supplier->tax_id)->toBe('900111222')
        ->and($supplier->payment_terms)->toBe(PaymentTerms::Prepaid)
        ->and($supplier->is_active)->toBeTrue();
});

it('creates a foreign supplier without RNT', function (): void {
    actingAs(supplierManager());

    Livewire::test(SupplierForm::class)
        ->set('legal_name', 'Global Hotels Inc.')
        ->set('trade_name', 'Global Hotels')
        ->set('tax_id', 'US-998877')
        ->set('country', 'us')
        ->set('payment_days', '30')
        ->set('payment_currency', 'usd')
        ->call('save')
        ->assertHasNoErrors();

    expect(Supplier::query()->sole()->country)->toBe('US');
});

it('validates supplier data', function (string $field, string $value): void {
    Supplier::factory()->create(['tax_id' => '900111222', 'country' => 'CO']);
    actingAs(supplierManager());

    Livewire::test(SupplierForm::class)
        ->set('legal_name', 'X')->set('trade_name', 'X')->set('tax_id', '800999999')->set('payment_days', '15')
        ->set('rnt_number', '1')->set('rnt_expires_on', '2030-01-01')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors($field);
})->with([
    'duplicated tax id in same country' => ['tax_id', '900111222'],
    'too many payment days' => ['payment_days', '999'],
    'currency not iso' => ['payment_currency', 'PESOS'],
    'website without https' => ['website', 'http://x.test'],
]);

it('edits a supplier', function (): void {
    $supplier = Supplier::factory()->create();
    actingAs(supplierManager());

    Livewire::test(SupplierForm::class, ['supplier' => $supplier])
        ->assertSet('tax_id', $supplier->tax_id)
        ->set('trade_name', 'Nuevo nombre')
        ->call('save')
        ->assertHasNoErrors();

    expect($supplier->fresh()?->trade_name)->toBe('Nuevo nombre');
});

it('computes the standing for new bookings', function (string $state, SupplierStanding $expected): void {
    $supplier = match ($state) {
        'valid' => Supplier::factory()->create(),
        'expiring' => Supplier::factory()->rntExpiringIn(10)->create(),
        'expired' => Supplier::factory()->rntExpiringIn(-1)->create(),
        'missing' => Supplier::factory()->create(['rnt_number' => null, 'rnt_expires_on' => null]),
        'foreign' => Supplier::factory()->foreign()->create(),
        'inactive' => Supplier::factory()->inactive()->create(),
    };

    $standing = app(SupplierDirectory::class)->standing($supplier->id, CarbonImmutable::today());

    expect($standing)->toBe($expected)
        ->and($standing->isBookable())->toBe(in_array($expected, [SupplierStanding::Bookable, SupplierStanding::RntExpiring], true))
        ->and($standing->label())->not->toStartWith('suppliers.');
})->with([
    ['valid', SupplierStanding::Bookable],
    ['expiring', SupplierStanding::RntExpiring],
    ['expired', SupplierStanding::RntExpired],
    ['missing', SupplierStanding::RntMissing],
    ['foreign', SupplierStanding::Bookable],
    ['inactive', SupplierStanding::Inactive],
]);

it('treats an unknown supplier as not bookable', function (): void {
    expect(app(SupplierDirectory::class)->standing(999999, CarbonImmutable::today()))->toBe(SupplierStanding::Inactive);
});

it('filters suppliers needing attention', function (): void {
    $ok = Supplier::factory()->create(['trade_name' => 'Proveedor al día']);
    $expiring = Supplier::factory()->rntExpiringIn(5)->create(['trade_name' => 'Proveedor por vencer']);
    actingAs(agent());

    Livewire::test(SuppliersIndex::class)
        ->assertSee($ok->trade_name)
        ->set('onlyAttention', true)
        ->assertSee($expiring->trade_name)
        ->assertDontSee($ok->trade_name)
        ->set('onlyAttention', false)
        ->set('search', 'no-existe')
        ->assertSee(__('suppliers.empty_title'));
});

it('agrees commissions per product type and resolves the one in force', function (): void {
    $supplier = Supplier::factory()->create();
    addCommission($supplier, '2026-01-01', '2026-06-30', rate: '10');
    addCommission($supplier, '2026-07-01', rate: '12.5');
    addCommission($supplier, '2026-01-01', type: ProductType::Tour, rate: '15');

    $directory = app(SupplierDirectory::class);
    $march = $directory->commissionFor($supplier->id, ProductType::Hotel, CarbonImmutable::parse('2026-03-15'));
    $august = $directory->commissionFor($supplier->id, ProductType::Hotel, CarbonImmutable::parse('2026-08-01'));

    expect($march?->rate->basisPoints)->toBe(1000)
        ->and($march?->base)->toBe(CommissionBase::Gross)
        ->and($august?->rate->basisPoints)->toBe(1250)
        ->and($directory->commissionFor($supplier->id, ProductType::Tour, CarbonImmutable::parse('2026-08-01'))?->rate->basisPoints)->toBe(1500)
        ->and($directory->commissionFor($supplier->id, ProductType::Flight, CarbonImmutable::parse('2026-08-01')))->toBeNull()
        ->and($directory->commissionFor($supplier->id, ProductType::Hotel, CarbonImmutable::parse('2025-12-31')))->toBeNull();
});

it('rejects overlapping commissions and inverted validity', function (): void {
    $supplier = Supplier::factory()->create();
    addCommission($supplier, '2026-01-01');

    expect(fn(): \App\Modules\Suppliers\Models\SupplierCommission => addCommission($supplier, '2026-05-01', '2026-05-31'))->toThrow(SupplierRuleViolation::class, __('suppliers.errors.overlapping_commission'))
        ->and(fn(): \App\Modules\Suppliers\Models\SupplierCommission => addCommission($supplier, '2027-05-01', '2027-04-01', ProductType::Car))->toThrow(SupplierRuleViolation::class, __('suppliers.errors.invalid_validity'));
});

it('ends a commission so a new one can be agreed', function (): void {
    $supplier = Supplier::factory()->create();
    $commission = addCommission($supplier, '2026-01-01');

    app(EndCommissionAction::class)->execute($commission, CarbonImmutable::parse('2026-03-31'));
    addCommission($supplier, '2026-04-01', rate: '8');

    expect($supplier->commissions()->count())->toBe(2)
        ->and(fn() => app(EndCommissionAction::class)->execute($commission, CarbonImmutable::parse('2025-01-01')))->toThrow(SupplierRuleViolation::class);
});

it('manages commissions and contacts from the supplier screen', function (): void {
    $supplier = Supplier::factory()->create();
    actingAs(supplierManager());

    $screen = Livewire::test(SupplierShow::class, ['supplier' => $supplier])
        ->set('commission.product_type', ProductType::Hotel->value)
        ->set('commission.rate', '10.5')
        ->set('commission.base', CommissionBase::Net->value)
        ->set('commission.valid_from', '2026-01-01')
        ->call('addCommission')
        ->assertHasNoErrors()
        ->assertSee('10.50 %')
        ->set('commission.product_type', ProductType::Hotel->value)
        ->set('commission.rate', '9')
        ->set('commission.valid_from', '2026-02-01')
        ->call('addCommission')
        ->assertHasErrors('commission.valid_from')
        ->set('contact.name', 'Ana Reservas')
        ->set('contact.email', 'reservas@hotel.test')
        ->call('addContact')
        ->assertHasNoErrors()
        ->assertSee('Ana Reservas');

    $screen->call('endCommission', $supplier->commissions()->firstOrFail()->id);
    expect($supplier->commissions()->firstOrFail()->valid_until)->not->toBeNull();

    $screen->call('toggleActive');
    expect($supplier->fresh()?->is_active)->toBeFalse();
});

it('validates commission input and hides management from agents', function (): void {
    $supplier = Supplier::factory()->create();
    actingAs(supplierManager());

    Livewire::test(SupplierShow::class, ['supplier' => $supplier])
        ->set('commission.product_type', 'spaceship')
        ->set('commission.rate', '150')
        ->call('addCommission')
        ->assertHasErrors(['commission.product_type', 'commission.rate', 'commission.valid_from']);

    actingAs(agent());
    Livewire::test(SupplierShow::class, ['supplier' => $supplier])
        ->assertDontSee(__('suppliers.add_commission'))
        ->call('toggleActive')
        ->assertForbidden();
});

it('lets only finance register and reveal bank accounts, with audit', function (): void {
    $supplier = Supplier::factory()->create();
    $finance = financeUser();
    actingAs($finance);

    Livewire::test(SupplierShow::class, ['supplier' => $supplier])
        ->set('account.bank_name', 'Banco Andino')
        ->set('account.account_type', BankAccountType::Checking->value)
        ->set('account.number', '1234-5678-90')
        ->set('account.holder_name', $supplier->legal_name)
        ->call('addBankAccount')
        ->assertHasNoErrors()
        ->assertSee('12••••••90')
        ->assertDontSee('1234567890')
        ->set('revealReason', 'Programar pago')
        ->call('revealAccount', $supplier->bankAccounts()->firstOrFail()->ulid)
        ->assertSee('1234567890');

    expect(DB::table('supplier_bank_accounts')->value('account_number'))->not->toContain('1234567890')
        ->and(SensitiveDataAccess::query()->where('field', 'account_number')->count())->toBe(1);
});

it('hides bank accounts from non finance users and blocks the actions', function (): void {
    $supplier = Supplier::factory()->create();
    $manager = supplierManager();
    actingAs($manager);

    Livewire::test(SupplierShow::class, ['supplier' => $supplier])
        ->assertDontSee(__('suppliers.bank_accounts'))
        ->set('account.bank_name', 'X')->set('account.number', '111')->set('account.holder_name', 'X')
        ->call('addBankAccount')
        ->assertHasErrors(['account.number' => __('suppliers.errors.bank_data_forbidden')]);

    expect(fn() => app(AddBankAccountAction::class)->execute($supplier, 'X', BankAccountType::Savings, '1', 'X', 'COP', $manager))
        ->toThrow(SupplierRuleViolation::class);
});

it('labels supplier enums and exposes error codes', function (): void {
    foreach ([...PaymentTerms::cases(), ...CommissionBase::cases(), ...BankAccountType::cases()] as $case) {
        expect($case->label())->not->toStartWith('suppliers.');
    }

    expect(SupplierRuleViolation::rntRequired()->errorCode())->toBe('rnt_required');
});
