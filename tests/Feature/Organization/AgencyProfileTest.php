<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Livewire\AgencyProfileForm;
use App\Modules\Organization\Models\AgencyProfile;
use App\Modules\Organization\Services\NitCheckDigit;
use App\Modules\Shared\Accessibility\ContrastRatio;
use App\Modules\Shared\Enums\Permission;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/** @return array<string, string> */
function validAgencyInput(array $overrides = []): array
{
    return array_merge([
        'legal_name' => 'Viajes del Caribe S.A.S.',
        'trade_name' => 'Viajes del Caribe',
        'nit' => '900123456',
        'nit_check_digit' => '8',
        'rnt_number' => '12345',
        'rnt_expires_on' => '2027-06-30',
        'address' => 'Calle 10 # 5-20',
        'city' => 'Cartagena',
        'phone' => '6056600000',
        'email' => 'hola@viajesdelcaribe.test',
        'website' => 'https://viajesdelcaribe.test',
        'brand_primary_color' => '#1d4ed8',
        'brand_accent_color' => '#9a3412',
    ], $overrides);
}

function owner(): User
{
    return userWithRole(Role::AgencyOwner);
}

function fillAgencyForm(Testable $component, array $input): Testable
{
    foreach ($input as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

it('calculates the dian check digit', function (string $nit, int $digit): void {
    expect(NitCheckDigit::for($nit))->toBe($digit);
})->with([
    ['900123456', 8],
    ['860034313', 7],
    ['800197268', 4],
    ['890900608', 9],
]);

it('measures wcag contrast', function (): void {
    expect(ContrastRatio::between('#ffffff', '#000000'))->toBe(21.0)
        ->and(ContrastRatio::meetsAa('#ffffff', '#1d4ed8'))->toBeTrue()
        ->and(ContrastRatio::meetsAa('#ffffff', '#fde047'))->toBeFalse()
        ->and(ContrastRatio::isHex('#12AbEf'))->toBeTrue()
        ->and(ContrastRatio::isHex('blue'))->toBeFalse();
});

it('rejects malformed colors when measuring contrast', function (): void {
    ContrastRatio::between('#fff', '#000000');
})->throws(InvalidArgumentException::class);

it('redirects guests to login', function (): void {
    get(route('organization.agency'))->assertRedirect(route('login'));
});

it('forbids users without the organization permission', function (): void {
    actingAs(agent())->get(route('organization.agency'))->assertForbidden();
});

it('shows the form and the navigation entry to the agency owner', function (): void {
    actingAs(owner())->get(route('organization.agency'))
        ->assertOk()
        ->assertSee(__('organization.agency.title'))
        ->assertSee('aria-current="page"', false);
});

it('creates the agency profile, audits it and applies the brand', function (): void {
    Storage::fake('public');
    $user = owner();
    actingAs($user);

    fillAgencyForm(Livewire::test(AgencyProfileForm::class), validAgencyInput())
        ->set('logo', UploadedFile::fake()->image('logo.png', 200, 80))
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('organization.agency'));

    $profile = AgencyProfile::current();
    expect($profile)->not->toBeNull()
        ->and($profile->nit_check_digit)->toBe(8)
        ->and($profile->formattedNit())->toBe('900.123.456-8')
        ->and(Activity::query()->where('log_name', 'organization')->where('subject_type', $profile->getMorphClass())->exists())->toBeTrue();
    Storage::disk('public')->assertExists((string) $profile->logo_path);

    get(route('dashboard'))
        ->assertSee('--brand-primary:#1d4ed8', false)
        ->assertSee('Viajes del Caribe');
});

it('loads the existing profile and replaces the old logo', function (): void {
    Storage::fake('public');
    Storage::disk('public')->put('branding/old.png', 'x');
    AgencyProfile::factory()->create(['trade_name' => 'Agencia Existente', 'logo_path' => 'branding/old.png']);
    actingAs(owner());

    $component = Livewire::test(AgencyProfileForm::class)->assertSet('trade_name', 'Agencia Existente');

    fillAgencyForm($component, validAgencyInput(['trade_name' => 'Agencia Renovada']))
        ->set('logo', UploadedFile::fake()->image('new.png'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('branding/old.png');
    expect(AgencyProfile::query()->count())->toBe(1)
        ->and(AgencyProfile::current()?->trade_name)->toBe('Agencia Renovada');
});

it('validates the agency data', function (array $input, string $field): void {
    actingAs(owner());

    fillAgencyForm(Livewire::test(AgencyProfileForm::class), validAgencyInput($input))
        ->call('save')
        ->assertHasErrors($field);

    expect(AgencyProfile::query()->count())->toBe(0);
})->with([
    'wrong check digit' => [['nit_check_digit' => '3'], 'nit_check_digit'],
    'nit with letters' => [['nit' => '90012A456'], 'nit'],
    'missing legal name' => [['legal_name' => ''], 'legal_name'],
    'invalid email' => [['email' => 'no-es-correo'], 'email'],
    'website without https' => [['website' => 'http://inseguro.test'], 'website'],
    'color not hex' => [['brand_primary_color' => 'blue'], 'brand_primary_color'],
    'color without contrast' => [['brand_accent_color' => '#fde047'], 'brand_accent_color'],
]);

it('rejects svg and oversized logos', function (UploadedFile $file): void {
    Storage::fake('public');
    actingAs(owner());

    fillAgencyForm(Livewire::test(AgencyProfileForm::class), validAgencyInput())
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors('logo');
})->with([
    'svg' => [fn() => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
    'too big' => [fn() => UploadedFile::fake()->image('logo.png')->size(5000)],
]);

it('warns when the rnt expires soon', function (): void {
    actingAs(owner());
    $soon = CarbonImmutable::today()->addDays(config()->integer('travel.organization.rnt_expiry_warning_days') - 1)->toDateString();

    Livewire::test(AgencyProfileForm::class)
        ->set('rnt_expires_on', $soon)
        ->assertSee(__('organization.agency.rnt_expiring'))
        ->set('rnt_expires_on', '2099-01-01')
        ->assertDontSee(__('organization.agency.rnt_expiring'));
});

it('knows whether the rnt expires within a window', function (): void {
    $profile = AgencyProfile::factory()->make(['rnt_expires_on' => '2026-11-01']);

    expect($profile->rntExpiresWithin(30, CarbonImmutable::parse('2026-10-15')))->toBeTrue()
        ->and($profile->rntExpiresWithin(30, CarbonImmutable::parse('2026-09-01')))->toBeFalse();
});

it('translates every permission', function (): void {
    foreach (Permission::cases() as $permission) {
        expect($permission->label())->not->toStartWith('shared.');
    }
});
