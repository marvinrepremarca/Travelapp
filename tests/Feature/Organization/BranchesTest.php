<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Actions\SaveBranchAction;
use App\Modules\Organization\Data\BranchData;
use App\Modules\Organization\Enums\BranchStatusFilter;
use App\Modules\Organization\Exceptions\InvalidBranchManager;
use App\Modules\Organization\Livewire\BranchesIndex;
use App\Modules\Organization\Livewire\BranchForm;
use App\Modules\Organization\Models\Branch;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function branchAdmin(): User
{
    return userWithRole(Role::AgencyOwner);
}

it('redirects guests to login', function (string $route): void {
    get(route($route))->assertRedirect(route('login'));
})->with(['organization.branches.index', 'organization.branches.create']);

it('forbids users without the branches permission', function (string $route): void {
    actingAs(agent())->get(route($route))->assertForbidden();
})->with(['organization.branches.index', 'organization.branches.create']);

it('forbids editing a branch without permission', function (): void {
    actingAs(agent())->get(route('organization.branches.edit', Branch::factory()->create()))->assertForbidden();
});

it('lists branches with manager names, paginated and without lazy loading', function (): void {
    $manager = userWithRole(Role::BranchManager);
    Branch::factory()->create(['name' => 'Bogotá Centro']);
    $managed = Branch::factory()->create(['name' => 'Medellín Poblado']);
    $managed->manager_id = $manager->id;
    $managed->save();

    actingAs(branchAdmin())->get(route('organization.branches.index'))
        ->assertOk()
        ->assertSee('Bogotá Centro')
        ->assertSee('Medellín Poblado')
        ->assertSee($manager->name)
        ->assertSee(__('organization.branches.no_manager'));
});

it('paginates with the configured page size', function (): void {
    config(['travel.organization.branches_per_page' => 2]);
    actingAs(branchAdmin());
    Branch::factory()->count(3)->create();
    $total = Branch::query()->count();

    Livewire::test(BranchesIndex::class)
        ->assertViewHas('branches', fn($branches): bool => $branches->count() === 2 && $branches->total() === $total);
});

it('filters by search and status', function (): void {
    Branch::factory()->create(['name' => 'Cartagena Bocagrande', 'code' => 'CTG-01']);
    Branch::factory()->inactive()->create(['name' => 'Cali Norte', 'code' => 'CLO-01']);
    actingAs(branchAdmin());

    Livewire::test(BranchesIndex::class)
        ->set('search', 'CTG')
        ->assertSee('Cartagena Bocagrande')
        ->assertDontSee('Cali Norte')
        ->set('search', '')
        ->set('status', BranchStatusFilter::Inactive->value)
        ->assertSee('Cali Norte')
        ->assertDontSee('Cartagena Bocagrande');
});

it('shows an empty state', function (): void {
    actingAs(branchAdmin());

    Livewire::test(BranchesIndex::class)
        ->set('search', 'no-existe')
        ->assertSee(__('organization.branches.empty_title'));
});

it('creates a branch with an eligible manager and audits it', function (): void {
    $manager = userWithRole(Role::BranchManager);
    actingAs(branchAdmin());

    Livewire::test(BranchForm::class)
        ->assertSet('timezone', config('travel.agency.timezone'))
        ->set('code', 'bog-02')
        ->set('name', 'Bogotá Usaquén')
        ->set('city', 'Bogotá')
        ->set('email', 'usaquen@agencia.test')
        ->set('manager_id', (string) $manager->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('organization.branches.index'));

    $branch = Branch::query()->where('code', 'BOG-02')->firstOrFail();
    expect($branch->is_active)->toBeTrue()
        ->and($branch->manager_id)->toBe($manager->id)
        ->and(Activity::query()->where('subject_type', $branch->getMorphClass())->where('subject_id', $branch->id)->exists())->toBeTrue();
});

it('edits a branch keeping its status', function (): void {
    $branch = Branch::factory()->inactive()->create(['code' => 'MDE-01', 'name' => 'Medellín']);
    actingAs(branchAdmin());

    Livewire::test(BranchForm::class, ['branch' => $branch])
        ->assertSet('code', 'MDE-01')
        ->set('name', 'Medellín El Poblado')
        ->call('save')
        ->assertHasNoErrors();

    expect($branch->fresh()?->name)->toBe('Medellín El Poblado')
        ->and($branch->fresh()?->is_active)->toBeFalse();
});

it('rejects a manager without the branch manager role', function (): void {
    actingAs(branchAdmin());

    Livewire::test(BranchForm::class)
        ->set('code', 'PEI-01')
        ->set('name', 'Pereira')
        ->set('manager_id', (string) agent()->id)
        ->call('save')
        ->assertHasErrors(['manager_id' => __('organization.errors.branch_manager_not_eligible')]);

    expect(Branch::query()->where('code', 'PEI-01')->exists())->toBeFalse();
});

it('rejects an inactive manager in the action', function (): void {
    $manager = userWithRole(Role::BranchManager);
    $manager->is_active = false;
    $manager->save();

    app(SaveBranchAction::class)->execute(new BranchData('X-1', 'X', null, null, null, null, 'America/Bogota', $manager->id));
})->throws(InvalidBranchManager::class);

it('validates branch fields', function (string $field, string $value): void {
    Branch::factory()->create(['code' => 'DUP-01']);
    actingAs(branchAdmin());

    Livewire::test(BranchForm::class)
        ->set('code', 'NEW-01')
        ->set('name', 'Nueva')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors($field);
})->with([
    'duplicated code' => ['code', 'DUP-01'],
    'code with spaces' => ['code', 'BOG 01'],
    'missing name' => ['name', ''],
    'invalid email' => ['email', 'correo'],
    'unknown timezone' => ['timezone', 'Mars/Olympus'],
]);

it('deactivates and reactivates a branch', function (): void {
    $branch = Branch::factory()->create(['name' => 'Santa Marta']);
    actingAs(branchAdmin());

    Livewire::test(BranchesIndex::class)->call('toggleActive', $branch->ulid);
    expect($branch->fresh()?->is_active)->toBeFalse();

    Livewire::test(BranchesIndex::class)->call('toggleActive', $branch->ulid);
    expect($branch->fresh()?->is_active)->toBeTrue()
        ->and(Branch::query()->active()->whereKey($branch->id)->exists())->toBeTrue();
});

it('forbids toggling a branch without permission', function (): void {
    $branch = Branch::factory()->create();
    $owner = branchAdmin();
    actingAs($owner);
    $component = Livewire::test(BranchesIndex::class);

    $owner->syncRoles([Role::TravelAgent->value]);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $component->call('toggleActive', $branch->ulid)->assertForbidden();
    expect($branch->fresh()?->is_active)->toBeTrue();
});

it('labels the status filters and exposes the manager error code', function (): void {
    expect(BranchStatusFilter::Active->label())->toBe('Activas')
        ->and(InvalidBranchManager::notEligible()->errorCode())->toBe('invalid_branch_manager');
});
