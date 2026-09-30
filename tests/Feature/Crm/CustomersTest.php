<?php

declare(strict_types=1);

use App\Modules\Audit\Models\SensitiveDataAccess;
use App\Modules\Crm\Actions\CreateCustomerAction;
use App\Modules\Crm\Actions\RevealCustomerFieldAction;
use App\Modules\Crm\Data\ConsentData;
use App\Modules\Crm\Data\CustomerData;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Enums\CustomerType;
use App\Modules\Crm\Enums\DocumentType;
use App\Modules\Crm\Enums\SensitiveCustomerField;
use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Livewire\CustomerForm;
use App\Modules\Crm\Livewire\CustomerShow;
use App\Modules\Crm\Livewire\CustomersIndex;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\CustomerConsent;
use App\Modules\Crm\Support\Mask;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function fillPerson(Testable $component, string $document = '1.020.304.050'): Testable
{
    return $component
        ->set('type', CustomerType::Person->value)
        ->set('first_name', 'María')
        ->set('last_name', 'Rodríguez')
        ->set('document_type', DocumentType::CitizenId->value)
        ->set('document_number', $document)
        ->set('birth_date', '1990-05-17')
        ->set('email', 'Maria@Correo.test')
        ->set('phone', '3001234567');
}

it('requires authentication', function (): void {
    get(route('crm.customers.index'))->assertRedirect(route('login'));
});

it('creates a person with encrypted document, consent evidence and audit without secrets', function (): void {
    $agent = agent();
    actingAs($agent);

    fillPerson(Livewire::test(CustomerForm::class))
        ->set('consent_data_processing', true)
        ->set('consent_channel', ConsentChannel::WhatsApp->value)
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::query()->sole();
    expect($customer->document_number)->toBe('1020304050')
        ->and($customer->display_name)->toBe('María Rodríguez')
        ->and($customer->email)->toBe('maria@correo.test')
        ->and($customer->owner_id)->toBe($agent->id)
        ->and($customer->birth_date?->toDateString())->toBe('1990-05-17')
        ->and($customer->hasConsent(ConsentPurpose::DataProcessing))->toBeTrue()
        ->and($customer->hasConsent(ConsentPurpose::Marketing))->toBeFalse();

    $raw = DB::table('customers')->where('id', $customer->id)->first();
    expect($raw->document_number)->not->toContain('1020304050')
        ->and($raw->birth_date)->not->toContain('1990');

    $activity = json_encode(Activity::query()->where('subject_id', $customer->id)->where('subject_type', $customer->getMorphClass())->firstOrFail()->properties);
    expect($activity)->not->toContain('1020304050')->not->toContain('1990-05-17');

    $consent = CustomerConsent::query()->where('purpose', ConsentPurpose::DataProcessing)->firstOrFail();
    expect($consent->channel)->toBe(ConsentChannel::WhatsApp)
        ->and($consent->policy_version)->toBe(config('travel.privacy.policy_version'))
        ->and($consent->recorded_by)->toBe($agent->id);
});

it('does not register a customer without data processing consent', function (): void {
    actingAs(agent());

    fillPerson(Livewire::test(CustomerForm::class))
        ->call('save')
        ->assertHasErrors(['consent_data_processing' => __('crm.errors.consent_required')]);

    expect(Customer::query()->count())->toBe(0);
});

it('rejects a duplicated document even written differently and without revealing the owner', function (): void {
    Customer::factory()->ownedBy(User::factory()->create())->create(['document_type' => DocumentType::CitizenId, 'document_number' => '1020304050']);
    actingAs(agent());

    fillPerson(Livewire::test(CustomerForm::class), '1-020-304-050')
        ->set('consent_data_processing', true)
        ->call('save')
        ->assertHasErrors(['document_number' => __('crm.errors.duplicate_document')]);
});

it('warns about a possible duplicate by email or phone', function (): void {
    Customer::factory()->create(['email' => 'repetido@correo.test']);
    actingAs(agent());

    Livewire::test(CustomerForm::class)
        ->set('email', 'Repetido@correo.test')
        ->assertSet('possibleDuplicate', true)
        ->assertSee(__('crm.customers.possible_duplicate'))
        ->set('email', 'nuevo@correo.test')
        ->assertSet('possibleDuplicate', false);
});

it('creates a company with NIT and rejects person documents for companies', function (): void {
    actingAs(agent());

    Livewire::test(CustomerForm::class)
        ->set('type', CustomerType::Company->value)
        ->set('legal_name', 'Constructora Andes S.A.S.')
        ->set('document_type', DocumentType::Nit->value)
        ->set('document_number', '900.123.456')
        ->set('consent_data_processing', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Customer::query()->sole()->display_name)->toBe('Constructora Andes S.A.S.');

    expect(fn() => app(CreateCustomerAction::class)->execute(
        new CustomerData(CustomerType::Company, DocumentType::CitizenId, '123', legalName: 'X'),
        new ConsentData(true, false, ConsentChannel::Email),
        agent(),
    ))->toThrow(CustomerRuleViolation::class);
});

it('validates the customer form', function (string $field, string $value): void {
    actingAs(agent());

    fillPerson(Livewire::test(CustomerForm::class))
        ->set('consent_data_processing', true)
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors($field);
})->with([
    'missing first name' => ['first_name', ''],
    'document with symbols' => ['document_number', '12#45'],
    'future birth date' => ['birth_date', '2999-01-01'],
    'invalid email' => ['email', 'correo'],
    'country not iso' => ['country', 'Colombia'],
]);

it('lists customers by scope and searches by name, email and exact document', function (): void {
    $agent = agent();
    $mine = Customer::factory()->ownedBy($agent)->create(['display_name' => 'Cliente Mío', 'document_number' => '555666777', 'email' => 'mio@correo.test']);
    Customer::factory()->ownedBy(User::factory()->create())->create(['display_name' => 'Cliente Ajeno']);
    actingAs($agent);

    Livewire::test(CustomersIndex::class)
        ->assertSee('Cliente Mío')
        ->assertDontSee('Cliente Ajeno')
        ->assertSee(Mask::value('555666777'))
        ->assertDontSee('555666777')
        ->set('search', '555.666.777')
        ->assertSee('Cliente Mío')
        ->set('search', 'mio@correo')
        ->assertSee('Cliente Mío')
        ->set('search', 'Ajeno')
        ->assertSee(__('crm.customers.empty_title'))
        ->set('search', '')
        ->set('type', CustomerType::Company->value)
        ->assertDontSee('Cliente Mío');

    expect($mine->maskedDocument())->toBe('55•••••77');
});

it('lets branch managers see the customers of their branch', function (): void {
    $manager = branchManager();
    $colleague = User::factory()->for($manager->branch)->create();
    $customer = Customer::factory()->ownedBy($colleague)->create();

    actingAs($manager)->get(route('crm.customers.show', $customer))->assertOk();
});

it('answers not found for customers outside the scope', function (): void {
    $customer = Customer::factory()->ownedBy(User::factory()->create())->create();
    actingAs(agent());

    get(route('crm.customers.show', $customer))->assertNotFound();
    get(route('crm.customers.edit', $customer))->assertNotFound();
});

it('edits a customer keeping the document when left empty', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['document_number' => '111222333']);
    actingAs($agent);

    Livewire::test(CustomerForm::class, ['customer' => $customer])
        ->assertSet('document_number', '')
        ->set('first_name', 'Nuevo')
        ->call('save')
        ->assertHasNoErrors();

    $customer->refresh();
    expect($customer->first_name)->toBe('Nuevo')
        ->and($customer->document_number)->toBe('111222333');
});

it('reveals sensitive data only with permission and records each access', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $customer = Customer::factory()->ownedBy($owner)->create(['document_number' => '987654321', 'birth_date' => '1985-02-03']);
    actingAs($owner);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->assertDontSee('987654321')
        ->call('reveal', SensitiveCustomerField::DocumentNumber->value)
        ->assertHasErrors('revealReason')
        ->set('revealReason', 'Emisión de tiquete')
        ->call('reveal', SensitiveCustomerField::DocumentNumber->value)
        ->assertSee('987654321')
        ->call('reveal', SensitiveCustomerField::BirthDate->value)
        ->assertSee('1985-02-03');

    expect(SensitiveDataAccess::query()->count())->toBe(2)
        ->and(SensitiveDataAccess::query()->first()?->reason)->toBe('Emisión de tiquete');
});

it('hides the reveal option from users without permission and blocks the action', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->assertDontSee(__('crm.customers.reveal_title'))
        ->set('revealReason', 'Curiosidad')
        ->call('reveal', SensitiveCustomerField::DocumentNumber->value)
        ->assertHasErrors(['revealReason' => __('crm.errors.sensitive_data_forbidden')]);

    expect(SensitiveDataAccess::query()->count())->toBe(0);
    expect(fn() => app(RevealCustomerFieldAction::class)->execute($customer, SensitiveCustomerField::BirthDate, $agent, 'x'))->toThrow(CustomerRuleViolation::class);
});

it('records marketing consent changes as new evidence', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(CustomerShow::class, ['customer' => $customer])->call('toggleMarketing');
    expect($customer->hasConsent(ConsentPurpose::Marketing))->toBeTrue();

    Livewire::test(CustomerShow::class, ['customer' => $customer])->call('toggleMarketing');
    expect($customer->hasConsent(ConsentPurpose::Marketing))->toBeFalse()
        ->and(CustomerConsent::query()->where('purpose', ConsentPurpose::Marketing)->count())->toBe(2);

    expect(fn() => CustomerConsent::query()->firstOrFail()->update(['granted' => false]))->toThrow(LogicException::class);
});

it('lets a branch manager reassign a customer within the branch', function (): void {
    $manager = branchManager();
    $from = User::factory()->for($manager->branch)->create();
    $to = User::factory()->for($manager->branch)->create();
    $customer = Customer::factory()->ownedBy($from)->create();
    actingAs($manager);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->set('newOwnerId', (string) $to->id)
        ->call('reassign')
        ->assertHasNoErrors();

    expect($customer->fresh()?->owner_id)->toBe($to->id);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->set('newOwnerId', (string) User::factory()->create()->id)
        ->call('reassign')
        ->assertHasErrors('newOwnerId');
});

it('does not let travel agents reassign customers', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->assertDontSee(__('crm.customers.reassign_title'))
        ->set('newOwnerId', (string) $agent->id)
        ->call('reassign')
        ->assertForbidden();
});

it('masks short and empty values', function (?string $value, string $expected): void {
    expect(Mask::value($value))->toBe($expected);
})->with([
    'null' => [null, ''],
    'short' => ['1234', '••••'],
    'tiny' => ['12', '•••'],
    'regular' => ['AB123456', 'AB••••56'],
]);

it('labels crm enums and exposes error codes', function (): void {
    foreach ([...CustomerType::cases(), ...DocumentType::cases(), ...ConsentPurpose::cases(), ...ConsentChannel::cases(), ...SensitiveCustomerField::cases()] as $case) {
        expect($case->label())->not->toStartWith('crm.');
    }

    expect(CustomerRuleViolation::duplicateDocument()->errorCode())->toBe('duplicate_document');
});

it('fails closed when the hash key is missing', function (): void {
    config(['travel.privacy.hash_key' => '']);

    app(App\Modules\Crm\Services\PersonalDataHasher::class)->hash('x', 'y');
})->throws(RuntimeException::class);

it('re-encrypts the document when it changes on edit', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['document_number' => '111222333']);
    actingAs($agent);

    Livewire::test(CustomerForm::class, ['customer' => $customer])
        ->set('document_number', '444.555.666')
        ->call('save')
        ->assertHasNoErrors();

    expect($customer->fresh()?->document_number)->toBe('444555666');
});

it('lists companies with their legal name', function (): void {
    $agent = agent();
    $company = Customer::factory()->company()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(CustomersIndex::class)
        ->set('type', CustomerType::Company->value)
        ->assertSee($company->display_name)
        ->assertSee(CustomerType::Company->label());
});

it('forbids reassignment to branch managers outside their scope with 404', function (): void {
    $response = Illuminate\Support\Facades\Gate::forUser(branchManager())->inspect('reassign', Customer::factory()->create());

    expect($response->status())->toBe(404);
});
