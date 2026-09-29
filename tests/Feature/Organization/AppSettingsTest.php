<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Organization\Actions\UpdateSettingsAction;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Enums\SettingKey;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Models\Setting;
use App\Modules\Organization\Services\SettingsStore;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/** @return array<string, mixed> */
function currentSettingValues(array $overrides = []): array
{
    $store = app(SettingsStore::class);
    $values = [];

    foreach (SettingKey::cases() as $key) {
        $values[$key->field()] = $store->get($key);
    }

    return array_merge($values, $overrides);
}

it('falls back to the travel configuration defaults', function (): void {
    config([
        'travel.agency.timezone' => 'America/Bogota',
        'travel.agency.default_currency' => 'COP',
        'travel.quotes.validity_hours' => 48,
        'travel.bookings.on_request_response_sla_hours' => 24,
        'travel.visibility.travel_agent_scope' => 'own',
        'travel.visibility.hide_margins_from_agents' => true,
    ]);

    $settings = app(AppSettings::class);

    expect($settings->agencyTimezone())->toBe('America/Bogota')
        ->and($settings->defaultCurrency())->toBe('COP')
        ->and($settings->quoteValidityHours())->toBe(48)
        ->and($settings->onRequestResponseSlaHours())->toBe(24)
        ->and($settings->travelAgentScope())->toBe(VisibilityScope::Own)
        ->and($settings->hideMarginsFromAgents())->toBeTrue()
        ->and(app(SettingsStore::class)->isOverridden(SettingKey::QuoteValidityHours))->toBeFalse();
});

it('stores only changed values, audits them and applies them immediately', function (): void {
    app(UpdateSettingsAction::class)->execute(currentSettingValues([
        SettingKey::QuoteValidityHours->field() => '96',
        SettingKey::TravelAgentScope->field() => 'branch',
        SettingKey::HideMarginsFromAgents->field() => '0',
    ]));

    $settings = app(AppSettings::class);

    expect(Setting::query()->count())->toBe(3)
        ->and($settings->quoteValidityHours())->toBe(96)
        ->and($settings->travelAgentScope())->toBe(VisibilityScope::Branch)
        ->and($settings->hideMarginsFromAgents())->toBeFalse()
        ->and(app(SettingsStore::class)->isOverridden(SettingKey::QuoteValidityHours))->toBeTrue()
        ->and(Activity::query()->where('log_name', 'organization')->count())->toBe(3);
});

it('gives new travel agents the configured scope', function (): void {
    app(UpdateSettingsAction::class)->execute(currentSettingValues([SettingKey::TravelAgentScope->field() => 'branch']));

    expect(Role::TravelAgent->defaultScope())->toBe(VisibilityScope::Branch);
});

it('validates each parameter', function (string $field, mixed $value): void {
    expect(fn() => app(UpdateSettingsAction::class)->execute(currentSettingValues([$field => $value])))
        ->toThrow(ValidationException::class);

    expect(Setting::query()->count())->toBe(0);
})->with([
    'unknown timezone' => ['agency__timezone', 'Mars/Olympus'],
    'lowercase currency' => ['agency__default_currency', 'cop'],
    'zero hours' => ['quotes__validity_hours', 0],
    'too many hours' => ['bookings__on_request_response_sla_hours', 721],
    'scope all is not allowed for agents' => ['visibility__travel_agent_scope', 'all'],
    'not a boolean' => ['visibility__hide_margins_from_agents', 'maybe'],
]);

it('labels every parameter in spanish', function (): void {
    foreach (SettingKey::cases() as $key) {
        expect($key->label())->not->toStartWith('organization.')
            ->and($key->help())->not->toStartWith('organization.');
    }
});

it('identifies branches publicly by ulid', function (): void {
    $branch = Branch::factory()->create();

    expect($branch->getRouteKey())->toBe($branch->ulid)
        ->and($branch->ulid)->toHaveLength(26);
});
