<?php

declare(strict_types=1);

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Models\Branch;

it('resolves business parameters from the travel configuration', function (): void {
    config([
        'travel.agency.timezone' => 'America/Bogota',
        'travel.agency.default_currency' => 'COP',
        'travel.quotes.validity_hours' => 48,
    ]);

    $settings = app(AppSettings::class);

    expect($settings->agencyTimezone())->toBe('America/Bogota')
        ->and($settings->defaultCurrency())->toBe('COP')
        ->and($settings->quoteValidityHours())->toBe(48);
});

it('identifies branches publicly by ulid', function (): void {
    $branch = Branch::factory()->create();

    expect($branch->getRouteKey())->toBe($branch->ulid)
        ->and($branch->ulid)->toHaveLength(26)
        ->and($branch->is_active)->toBeTrue();
});
