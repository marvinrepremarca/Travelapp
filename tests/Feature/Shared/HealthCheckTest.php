<?php

declare(strict_types=1);

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\getJson;

it('reports up when database and cache respond', function (): void {
    getJson(route('health'))
        ->assertOk()
        ->assertExactJson(['status' => 'up', 'checks' => ['database' => 'up', 'cache' => 'up']]);
});

it('reports down without leaking details when a dependency fails', function (): void {
    Log::spy();
    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('put')->andThrow(new RuntimeException('redis://secret-host refused'));
    app()->instance(Repository::class, $cache);

    getJson(route('health'))
        ->assertServiceUnavailable()
        ->assertExactJson(['status' => 'down', 'checks' => ['database' => 'up', 'cache' => 'down']])
        ->assertDontSee('secret-host');

    Log::shouldHaveReceived('error')->withArgs(fn(string $message, array $context): bool => $message === 'health_check_failed' && $context['check'] === 'cache');
});

it('reports down when the cache loses the probe', function (): void {
    $cache = Mockery::mock(Repository::class);
    $cache->shouldReceive('put')->andReturnTrue();
    $cache->shouldReceive('pull')->andReturnNull();
    app()->instance(Repository::class, $cache);

    getJson(route('health'))->assertServiceUnavailable()->assertJsonPath('checks.cache', 'down');
});

it('is rate limited', function (): void {
    config(['travel.health.requests_per_minute' => 2]);

    getJson(route('health'))->assertOk();
    getJson(route('health'))->assertOk();
    getJson(route('health'))->assertTooManyRequests();
});
