<?php

declare(strict_types=1);

use App\Modules\Audit\Models\SensitiveDataAccess;
use App\Modules\Customers\Enums\Gender;
use App\Modules\Customers\Livewire\CustomerShow;
use App\Modules\Customers\Livewire\TravelerForm;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\PassengerType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('adds a traveler with encrypted birth date and passport', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(TravelerForm::class, ['customer' => $customer])
        ->set('first_name', 'José Ángel')
        ->set('last_name', 'Peña Núñez')
        ->set('gender', Gender::Male->value)
        ->set('birth_date', '2015-03-10')
        ->set('nationality', 'co')
        ->set('passport_number', 'ab123456')
        ->set('passport_country', 'co')
        ->set('passport_expires_on', now()->addYears(3)->toDateString())
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('customers.show', $customer));

    $traveler = Traveler::query()->sole();
    expect($traveler->passport_number)->toBe('AB123456')
        ->and($traveler->nationality)->toBe('CO')
        ->and($traveler->airlineName())->toBe('PENA NUNEZ/JOSE ANGEL')
        ->and($traveler->passengerTypeAt(CarbonImmutable::parse('2026-10-01')))->toBe(PassengerType::Child);

    $raw = DB::table('travelers')->where('id', $traveler->id)->first();
    expect($raw->passport_number)->not->toContain('AB123456')
        ->and($raw->birth_date)->not->toContain('2015');
});

it('validates traveler data', function (string $field, string $value): void {
    $agent = agent();
    actingAs($agent);

    Livewire::test(TravelerForm::class, ['customer' => Customer::factory()->ownedBy($agent)->create()])
        ->set('first_name', 'Ana')
        ->set('last_name', 'Gómez')
        ->set('gender', Gender::Female->value)
        ->set('birth_date', '1990-01-01')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors();
})->with([
    'unknown gender' => ['gender', 'Z'],
    'future birth date' => ['birth_date', '2999-01-01'],
    'passport with symbols' => ['passport_number', 'AB-12'],
    'passport without expiry' => ['passport_number', 'AB123456'],
]);

it('edits a traveler keeping the passport when left empty', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    $traveler = Traveler::factory()->for($customer)->create();
    $traveler->passport_number = 'ZX998877';
    $traveler->save();
    actingAs($agent);

    Livewire::test(TravelerForm::class, ['customer' => $customer, 'traveler' => $traveler])
        ->set('first_name', 'Actualizado')
        ->call('save')
        ->assertHasNoErrors();

    expect($traveler->fresh()?->first_name)->toBe('Actualizado')
        ->and($traveler->fresh()?->passport_number)->toBe('ZX998877');
});

it('answers not found for travelers of customers outside the scope or of another customer', function (): void {
    $agent = agent();
    $foreignCustomer = Customer::factory()->ownedBy(User::factory()->create())->create();
    $mine = Customer::factory()->ownedBy($agent)->create();
    $othersTraveler = Traveler::factory()->for($foreignCustomer)->create();
    actingAs($agent);

    $this->get(route('customers.travelers.create', $foreignCustomer))->assertNotFound();
    $this->get(route('customers.travelers.edit', [$mine, $othersTraveler]))->assertNotFound();
});

it('lists travelers with masked passport, minor and expiry badges and reveals with audit', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $customer = Customer::factory()->ownedBy($owner)->create();
    $traveler = Traveler::factory()->for($customer)->child(5)->create(['passport_expires_on' => now()->addMonths(2)->toDateString()]);
    $traveler->passport_number = 'PP554433';
    $traveler->save();
    actingAs($owner);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->assertSee($traveler->fullName())
        ->assertSee('PP••••33')
        ->assertDontSee('PP554433')
        ->assertSee(PassengerType::Child->label())
        ->assertSee(__('customers.travelers.passport_expiring', ['date' => $traveler->passport_expires_on?->toDateString()]))
        ->set('revealReason', 'Reserva aérea')
        ->call('revealPassport', $traveler->ulid)
        ->assertSee('PP554433');

    expect(SensitiveDataAccess::query()->where('field', 'passport_number')->count())->toBe(1);
});

it('does not reveal passports without permission', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    $traveler = Traveler::factory()->for($customer)->create();
    actingAs($agent);

    Livewire::test(CustomerShow::class, ['customer' => $customer])
        ->set('revealReason', 'x')
        ->call('revealPassport', $traveler->ulid)
        ->assertHasErrors(['revealReason' => __('customers.errors.sensitive_data_forbidden')]);
});

it('checks passport validity after the return date', function (): void {
    $traveler = Traveler::factory()->make(['passport_expires_on' => '2027-03-01']);

    expect($traveler->passportValidFor(CarbonImmutable::parse('2026-08-31'), 6))->toBeTrue()
        ->and($traveler->passportValidFor(CarbonImmutable::parse('2026-09-02'), 6))->toBeFalse()
        ->and(Traveler::factory()->make(['passport_expires_on' => null])->passportValidFor(CarbonImmutable::now(), 6))->toBeFalse();
});

it('computes age at the service date, not today', function (): void {
    $traveler = Traveler::factory()->make(['birth_date' => '2024-11-15']);

    expect($traveler->passengerTypeAt(CarbonImmutable::parse('2026-11-14')))->toBe(PassengerType::Infant)
        ->and($traveler->passengerTypeAt(CarbonImmutable::parse('2026-11-15')))->toBe(PassengerType::Child);
});

it('shows an empty state and labels genders', function (): void {
    $agent = agent();
    actingAs($agent);

    Livewire::test(CustomerShow::class, ['customer' => Customer::factory()->ownedBy($agent)->create()])
        ->assertSee(__('customers.travelers.empty_title'));

    foreach (Gender::cases() as $gender) {
        expect($gender->label())->not->toStartWith('crm.');
    }
});
