<?php

declare(strict_types=1);

use App\Modules\Shared\Routing\PathPrefix;

use function Pest\Laravel\get;

it('publishes the application under the configured folder', function (): void {
    expect(PathPrefix::value())->toBe('travelapp')
        ->and(route('login', absolute: false))->toBe('/travelapp/login')
        ->and(route('dashboard', absolute: false))->toBe('/travelapp')
        ->and(route('crm.customers.index', absolute: false))->toBe('/travelapp/crm/customers')
        ->and(route('livewire.update', absolute: false))->toBe('/travelapp/livewire/update')
        ->and(config('fortify.home'))->toBe('/travelapp');
});

it('serves pages under the folder and not at the root', function (): void {
    get('/travelapp/login')->assertOk();
    get('/travelapp/up')->assertNoContent();
    get('/login')->assertNotFound();
});

it('builds prefixed paths', function (string $path, string $expected): void {
    expect(PathPrefix::path($path))->toBe($expected);
})->with([
    ['livewire/update', '/travelapp/livewire/update'],
    ['/up', '/travelapp/up'],
    ['', '/travelapp'],
]);

it('works at the domain root when the prefix is empty', function (): void {
    config(['app.path_prefix' => '']);

    expect(PathPrefix::path('livewire/update'))->toBe('/livewire/update')
        ->and(PathPrefix::path())->toBe('/');
});
