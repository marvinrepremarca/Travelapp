<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Navigation\MainMenu;

use function Pest\Laravel\actingAs;

it('orders the menu by setup stages with stable numbers', function (): void {
    $stages = app(MainMenu::class)->for(userWithRole(Role::AgencyOwner));

    expect(array_column($stages, 'key'))->toBe(['setup', 'products', 'sales', 'operations', 'control'])
        ->and($stages[0]['steps'][0])->toBe(['number' => '1.1', 'key' => 'agency', 'route' => 'organization.agency'])
        ->and(collect($stages)->firstWhere('key', 'sales')['steps'][4]['number'])->toBe('3.5');
});

it('hides steps without permission but keeps the numbering', function (): void {
    $stages = app(MainMenu::class)->for(agent());
    $products = collect($stages)->firstWhere('key', 'products');

    expect(array_column($stages, 'key'))->not->toContain('setup')
        ->and(array_column($products['steps'], 'number'))->toBe(['2.1', '2.2', '2.4', '2.5'])
        ->and($stages[0]['number'])->toBe(2);
});

it('shows the numbered menu and the startup guide on the home page', function (): void {
    actingAs(userWithRole(Role::AgencyOwner))->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('navigation.guide.title'))
        ->assertSee('1. ' . __('navigation.stages.setup.title'))
        ->assertSee(__('navigation.items.agency.hint'))
        ->assertSee(__('navigation.guide.flow'));

    actingAs(agent())->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('navigation.items.agency.label'))
        ->assertSee(__('navigation.items.quotes.label'));
});

it('marks the current page in the menu', function (): void {
    actingAs(agent())->get(route('quotes.index'))->assertSee('aria-current="page"', false);
});
