<?php

declare(strict_types=1);

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Enums\AuditTab;
use App\Modules\Audit\Enums\SecurityEvent;
use App\Modules\Audit\Enums\SensitiveDataAccessType;
use App\Modules\Audit\Livewire\AuditLogIndex;
use App\Modules\Audit\Models\SensitiveDataAccess;
use App\Modules\Identity\Enums\Role;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function auditor(): App\Modules\Identity\Models\User
{
    return userWithRole(Role::AgencyOwner);
}

it('protects the audit screen', function (): void {
    get(route('audit.index'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('audit.index'))->assertForbidden();
    actingAs(auditor())->get(route('audit.index'))->assertOk()->assertSee(__('audit.tabs.changes'));
});

it('records successful and failed logins without secrets', function (): void {
    $user = agent();

    post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    post(route('login'), ['email' => $user->email, 'password' => 'password']);
    post(route('logout'));

    $events = Activity::query()->where('log_name', AuditLogName::Security->value)->orderBy('id')->pluck('event')->all();
    expect($events)->toBe([SecurityEvent::LoginFailed->value, SecurityEvent::LoggedIn->value, SecurityEvent::LoggedOut->value]);

    $failed = Activity::query()->where('event', SecurityEvent::LoginFailed->value)->firstOrFail();
    expect($failed->getExtraProperty('identifier'))->toBe(mb_strtolower($user->email))
        ->and(json_encode($failed->properties))->not->toContain('wrong-password');
});

it('records lockouts', function (): void {
    event(new Lockout(Request::create('/login', 'POST', ['email' => 'Alguien@Agencia.test'])));

    expect(Activity::query()->where('event', SecurityEvent::LockedOut->value)->first()?->getExtraProperty('identifier'))
        ->toBe('alguien@agencia.test');
});

it('lists changes with old and new values and filters by module and dates', function (): void {
    $owner = auditor();
    $branch = Branch::factory()->create(['name' => 'Antes']);
    $branch->update(['name' => 'Después']);
    actingAs($owner);

    Livewire::test(AuditLogIndex::class)
        ->assertSee('Antes')
        ->assertSee('Después')
        ->set('logName', AuditLogName::Identity->value)
        ->assertDontSee('Después')
        ->set('logName', '')
        ->set('from', CarbonImmutable::tomorrow()->toDateString())
        ->assertSee(__('audit.empty_title'))
        ->set('from', CarbonImmutable::yesterday()->toDateString())
        ->set('to', CarbonImmutable::tomorrow()->toDateString())
        ->assertSee('Después')
        ->set('from', 'no-es-fecha')
        ->assertSee('Después');
});

it('shows security events in their own tab', function (): void {
    $user = agent();
    post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    actingAs(auditor());

    Livewire::test(AuditLogIndex::class)
        ->set('tab', AuditTab::Security->value)
        ->assertSee(SecurityEvent::LoginFailed->label())
        ->assertSee(mb_strtolower($user->email));
});

it('records sensitive data access through the contract and lists it', function (): void {
    $viewer = auditor();
    $subject = Branch::factory()->create();

    app(SensitiveDataAccessRecorder::class)->record($viewer, $subject, 'passport_number', SensitiveDataAccessType::Viewed, 'Emisión de tiquete');
    actingAs($viewer);

    Livewire::test(AuditLogIndex::class)
        ->set('tab', AuditTab::SensitiveAccess->value)
        ->assertSee('passport_number')
        ->assertSee('Emisión de tiquete')
        ->assertSee(SensitiveDataAccessType::Viewed->label())
        ->set('from', CarbonImmutable::tomorrow()->toDateString())
        ->assertSee(__('audit.empty_title'));
});

it('keeps sensitive access records immutable', function (Closure $mutate): void {
    app(SensitiveDataAccessRecorder::class)->record(auditor(), Branch::factory()->create(), 'birth_date', SensitiveDataAccessType::Exported);

    $mutate(SensitiveDataAccess::query()->firstOrFail());
})->with([
    'update' => [fn(SensitiveDataAccess $access) => $access->update(['reason' => 'cambiado'])],
    'delete' => [fn(SensitiveDataAccess $access) => $access->delete()],
])->throws(LogicException::class);

it('labels audit enums in spanish', function (): void {
    foreach ([...SecurityEvent::cases(), ...SensitiveDataAccessType::cases(), ...AuditTab::cases(), ...AuditLogName::cases()] as $case) {
        expect($case->label())->not->toStartWith('audit.');
    }

    expect(SecurityEvent::LoginFailed->tone()->value)->toBe('danger')
        ->and(SecurityEvent::TwoFactorEnabled->tone()->value)->toBe('warning')
        ->and(SecurityEvent::LoggedIn->tone()->value)->toBe('neutral')
        ->and(SensitiveDataAccessType::Viewed->tone()->value)->toBe('info')
        ->and(SensitiveDataAccessType::Exported->tone()->value)->toBe('warning');
});
