<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Livewire\HolidaysManager;
use App\Modules\Organization\Livewire\SettingsForm;
use App\Modules\Organization\Models\HolidayAdjustment;
use App\Modules\Organization\Models\Setting;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('protects the holidays and settings screens', function (string $route): void {
    get(route($route))->assertRedirect(route('login'));
    actingAs(agent())->get(route($route))->assertForbidden();
    actingAs(userWithRole(Role::AgencyOwner))->get(route($route))->assertOk();
})->with(['organization.holidays', 'organization.settings']);

it('shows the national calendar of the selected year', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(HolidaysManager::class)
        ->set('year', 2026)
        ->assertSee('Día de los Reyes Magos')
        ->assertSee(__('organization.holidays_screen.no_adjustments'));
});

it('adds and removes an agency day off', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));

    $component = Livewire::test(HolidaysManager::class)
        ->set('year', 2026)
        ->set('type', HolidayAdjustmentType::Add->value)
        ->set('date', '2026-12-24')
        ->set('name', 'Cierre de Nochebuena')
        ->call('addAdjustment')
        ->assertHasNoErrors()
        ->assertSee('Cierre de Nochebuena');

    expect(app(HolidayCalendar::class)->isHoliday(CarbonImmutable::parse('2026-12-24')))->toBeTrue();

    $component->call('removeAdjustment', HolidayAdjustment::query()->firstOrFail()->ulid);

    expect(HolidayAdjustment::query()->count())->toBe(0)
        ->and(app(HolidayCalendar::class)->isHoliday(CarbonImmutable::parse('2026-12-24')))->toBeFalse();
});

it('reports business rule and validation errors on the holiday form', function (string $type, string $date, string $name): void {
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(HolidaysManager::class)
        ->set('type', $type)
        ->set('date', $date)
        ->set('name', $name)
        ->call('addAdjustment')
        ->assertHasErrors();

    expect(HolidayAdjustment::query()->count())->toBe(0);
})->with([
    'national holiday added again' => ['add', '2026-01-12', 'Duplicado'],
    'working day removed' => ['remove', '2026-01-13', 'Sin festivo'],
    'missing name' => ['add', '2026-12-24', ''],
    'unknown type' => ['other', '2026-12-24', 'X'],
]);

it('forbids holiday actions after losing the permission', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner);
    $component = Livewire::test(HolidaysManager::class);

    $owner->syncRoles([Role::TravelAgent->value]);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $component->set('date', '2026-12-24')->set('name', 'X')->call('addAdjustment')->assertForbidden();
});

it('edits the parameters from the screen', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(SettingsForm::class)
        ->assertSet('values.visibility__hide_margins_from_agents', '1')
        ->set('values.quotes__validity_hours', '48')
        ->set('values.visibility__hide_margins_from_agents', '0')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('organization.settings'));

    expect(app(AppSettings::class)->quoteValidityHours())->toBe(48)
        ->and(app(AppSettings::class)->hideMarginsFromAgents())->toBeFalse();
});

it('shows parameter validation errors next to each field', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(SettingsForm::class)
        ->set('values.quotes__validity_hours', '0')
        ->call('save')
        ->assertHasErrors('values.quotes__validity_hours');

    expect(Setting::query()->count())->toBe(0);
});

it('forbids saving parameters after losing the permission', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner);
    $component = Livewire::test(SettingsForm::class);

    $owner->syncRoles([Role::TravelAgent->value]);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $component->call('save')->assertForbidden();
});

it('shows every organization screen in the navigation of the owner', function (): void {
    actingAs(userWithRole(Role::AgencyOwner))->get(route('dashboard'))
        ->assertSee(route('organization.agency'))
        ->assertSee(route('organization.branches.index'))
        ->assertSee(route('organization.holidays'))
        ->assertSee(route('organization.settings'));
});

it('hides organization screens from travel agents', function (): void {
    actingAs(agent())->get(route('dashboard'))
        ->assertDontSee(route('organization.agency'))
        ->assertDontSee(route('organization.settings'));
});
