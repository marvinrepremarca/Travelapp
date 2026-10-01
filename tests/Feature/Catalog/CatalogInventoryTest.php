<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\ToggleDepartureAction;
use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Catalog\Enums\SeatHoldStatus;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Models\CatalogSeatHold;
use Carbon\CarbonImmutable;

function departureWithCapacity(int $capacity): CatalogDeparture
{
    return app(AddDepartureAction::class)->execute(CatalogProduct::factory()->create(), CarbonImmutable::parse('2026-12-20'), '08:00', $capacity);
}

it('holds seats and discounts them from the departure', function (): void {
    $departure = departureWithCapacity(10);

    $holdUlid = app(CatalogInventory::class)->hold($departure->ulid, 4, 'EXP-1', 'key-1');

    expect($departure->fresh()?->availableSeats())->toBe(6)
        ->and(CatalogSeatHold::query()->where('ulid', $holdUlid)->sole()->status)->toBe(SeatHoldStatus::Held);
});

it('does not discount twice when the same request is retried', function (): void {
    $departure = departureWithCapacity(10);
    $inventory = app(CatalogInventory::class);

    $first = $inventory->hold($departure->ulid, 3, 'EXP-1', 'retry-key');
    $second = $inventory->hold($departure->ulid, 3, 'EXP-1', 'retry-key');

    expect($second)->toBe($first)
        ->and($departure->fresh()?->reserved_seats)->toBe(3);
});

it('never oversells a departure', function (): void {
    $departure = departureWithCapacity(5);
    $inventory = app(CatalogInventory::class);
    $inventory->hold($departure->ulid, 4, 'EXP-1', 'key-1');

    expect(fn() => $inventory->hold($departure->ulid, 2, 'EXP-2', 'key-2'))
        ->toThrow(CatalogRuleViolation::class, __('catalog.errors.sold_out', ['available' => 1]));
    expect($departure->fresh()?->reserved_seats)->toBe(4);
});

it('sells the last seats exactly to capacity', function (): void {
    $departure = departureWithCapacity(5);

    app(CatalogInventory::class)->hold($departure->ulid, 5, 'EXP-1', 'key-1');

    expect($departure->fresh()?->isSellable())->toBeFalse();
});

it('rejects holds on closed departures', function (): void {
    $departure = app(ToggleDepartureAction::class)->execute(departureWithCapacity(5));

    expect(fn() => app(CatalogInventory::class)->hold($departure->ulid, 1, 'EXP-1', 'key-1'))
        ->toThrow(CatalogRuleViolation::class, __('catalog.errors.departure_not_open'));
});

it('returns released seats once even if released twice', function (): void {
    $departure = departureWithCapacity(5);
    $inventory = app(CatalogInventory::class);
    $holdUlid = $inventory->hold($departure->ulid, 2, 'EXP-1', 'key-1');

    $inventory->release($holdUlid);
    $inventory->release($holdUlid);

    $hold = CatalogSeatHold::query()->where('ulid', $holdUlid)->sole();
    expect($departure->fresh()?->reserved_seats)->toBe(0)
        ->and($hold->status)->toBe(SeatHoldStatus::Released)
        ->and($hold->released_at)->not->toBeNull();
});

it('keeps held seats when a departure is closed and reopened', function (): void {
    $departure = departureWithCapacity(5);
    app(CatalogInventory::class)->hold($departure->ulid, 2, 'EXP-1', 'key-1');
    $toggle = app(ToggleDepartureAction::class);

    $reopened = $toggle->execute($toggle->execute($departure->fresh() ?? $departure));

    expect($reopened->availableSeats())->toBe(3)
        ->and($reopened->isSellable())->toBeTrue();
});
