<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogProduct;
use Carbon\CarbonImmutable;

function productWithSeasons(): CatalogProduct
{
    $product = CatalogProduct::factory()->create();
    $add = app(AddSeasonAction::class);
    $add->execute($product, 'Baja', CarbonImmutable::parse('2026-01-15'), CarbonImmutable::parse('2026-06-14'), ['adult' => 10000000, 'child' => 7000000, 'infant' => 0]);
    $add->execute($product, 'Alta', CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2027-01-14'), ['adult' => 15000000]);

    return $product;
}

it('prices each passenger by age at the service date and the season', function (string $date, array $ages, string $season, string $total): void {
    $quote = app(CatalogRates::class)->netPriceFor(productWithSeasons()->ulid, CarbonImmutable::parse($date), $ages);

    expect($quote->seasonName)->toBe($season)
        ->and((string) $quote->total->getAmount())->toBe($total);
})->with([
    'two adults in low season' => ['2026-03-10', [35, 40], 'Baja', '200000.00'],
    'family with child and infant' => ['2026-03-10', [35, 8, 1], 'Baja', '170000.00'],
    'twelve years old pays as adult' => ['2026-03-10', [12], 'Baja', '100000.00'],
    'season limits are inclusive' => ['2026-06-14', [30], 'Baja', '100000.00'],
    'high season crossing the year' => ['2027-01-02', [30, 30], 'Alta', '300000.00'],
]);

it('breaks the price down by passenger type', function (): void {
    $quote = app(CatalogRates::class)->netPriceFor(productWithSeasons()->ulid, CarbonImmutable::parse('2026-03-10'), [35, 40, 8]);

    expect($quote->lines['adult']['count'])->toBe(2)
        ->and((string) $quote->lines['child']['unit']->getAmount())->toBe('70000.00');
});

it('refuses dates without a season', function (): void {
    app(CatalogRates::class)->netPriceFor(productWithSeasons()->ulid, CarbonImmutable::parse('2026-08-01'), [30]);
})->throws(CatalogRuleViolation::class);

it('refuses passenger types without a rate in the season', function (): void {
    app(CatalogRates::class)->netPriceFor(productWithSeasons()->ulid, CarbonImmutable::parse('2026-12-20'), [30, 8]);
})->throws(CatalogRuleViolation::class);

it('refuses inactive products', function (): void {
    $product = productWithSeasons();
    $product->is_active = false;
    $product->save();

    app(CatalogRates::class)->netPriceFor($product->ulid, CarbonImmutable::parse('2026-03-10'), [30]);
})->throws(CatalogRuleViolation::class);

it('rejects overlapping seasons of the same product', function (string $from, string $until): void {
    $product = productWithSeasons();

    expect(fn() => app(AddSeasonAction::class)->execute($product, 'Cruce', CarbonImmutable::parse($from), CarbonImmutable::parse($until), ['adult' => 1]))
        ->toThrow(CatalogRuleViolation::class, __('catalog.errors.overlapping_season'));
})->with([
    'starts inside' => ['2026-06-01', '2026-07-01'],
    'ends inside' => ['2026-01-01', '2026-01-15'],
    'contains another' => ['2026-01-01', '2026-07-01'],
]);

it('allows adjacent seasons and seasons of other products', function (): void {
    $product = productWithSeasons();
    $add = app(AddSeasonAction::class);

    $add->execute($product, 'Media', CarbonImmutable::parse('2026-06-15'), CarbonImmutable::parse('2026-11-30'), ['adult' => 1]);
    $add->execute(CatalogProduct::factory()->create(), 'Baja', CarbonImmutable::parse('2026-01-15'), CarbonImmutable::parse('2026-06-14'), ['adult' => 1]);

    expect($product->seasons()->count())->toBe(3);
});
