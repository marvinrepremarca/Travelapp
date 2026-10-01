<?php

declare(strict_types=1);

use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Enums\FeeBasis;
use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Pricing\Enums\PriceComponentType;
use App\Modules\Pricing\Models\FeeRule;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Pricing\Models\TaxRule;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

function priceFor(string $net, string $currency = 'COP', array $overrides = []): App\Modules\Pricing\Data\PriceBreakdown
{
    $args = array_merge([
        'supplierNet' => Money::of($net, $currency),
        'saleCurrency' => 'COP',
        'productType' => ProductType::Hotel,
        'channel' => SalesChannel::Branch,
        'serviceDate' => CarbonImmutable::parse('2026-11-10'),
        'passengers' => 2,
        'nights' => 3,
    ], $overrides);

    return app(PriceCalculator::class)->calculate(new PriceRequest(...$args));
}

function amountOf(App\Modules\Pricing\Data\PriceBreakdown $breakdown, PriceComponentType $type): string
{
    return (string) $breakdown->totalOf($type)->getAmount();
}

it('sells at net when no rule applies', function (): void {
    $price = priceFor('1000000');

    expect((string) $price->total()->getAmount())->toBe('1000000.00')
        ->and($price->margin()->isZero())->toBeTrue()
        ->and($price->exchangeRate)->toBeNull();
});

it('applies percentage markup and IVA only over the agency income', function (): void {
    $this->seed(TaxReferenceSeeder::class);
    MarkupRule::factory()->percentage(1000)->create();

    $price = priceFor('1000000');

    expect(amountOf($price, PriceComponentType::SupplierNet))->toBe('1000000.00')
        ->and(amountOf($price, PriceComponentType::Markup))->toBe('100000.00')
        ->and(amountOf($price, PriceComponentType::Tax))->toBe('19000.00')
        ->and((string) $price->total()->getAmount())->toBe('1119000.00')
        ->and((string) $price->margin()->getAmount())->toBe('100000.00');
});

it('picks the most specific markup rule', function (array $request, string $expectedMarkup): void {
    $supplier = Supplier::factory()->create();
    MarkupRule::factory()->percentage(500)->create(['name' => 'general']);
    MarkupRule::factory()->percentage(800)->create(['name' => 'hoteles', 'product_type' => ProductType::Hotel]);
    MarkupRule::factory()->percentage(1200)->create(['name' => 'hoteles online', 'product_type' => ProductType::Hotel, 'sales_channel' => SalesChannel::Online]);
    MarkupRule::factory()->percentage(300)->create(['name' => 'proveedor', 'product_type' => ProductType::Hotel, 'supplier_id' => $supplier->id, 'destination_country' => 'CO']);

    $request = array_map(static fn(mixed $value): mixed => $value === 'SUPPLIER' ? $supplier->id : $value, $request);

    expect(amountOf(priceFor('1000000', overrides: $request), PriceComponentType::Markup))->toBe($expectedMarkup);
})->with([
    'tour falls back to general' => [['productType' => ProductType::Tour], '50000.00'],
    'hotel at branch' => [['productType' => ProductType::Hotel], '80000.00'],
    'hotel online' => [['channel' => SalesChannel::Online], '120000.00'],
    'supplier and destination win' => [['supplierId' => 'SUPPLIER', 'destinationCountry' => 'co'], '30000.00'],
]);

it('breaks ties by priority', function (): void {
    MarkupRule::factory()->percentage(500)->create(['product_type' => ProductType::Hotel, 'priority' => 1]);
    MarkupRule::factory()->percentage(900)->create(['product_type' => ProductType::Hotel, 'priority' => 5]);

    expect(amountOf(priceFor('1000000'), PriceComponentType::Markup))->toBe('90000.00');
});

it('ignores inactive and out of validity rules', function (): void {
    MarkupRule::factory()->percentage(5000)->create(['is_active' => false]);
    MarkupRule::factory()->percentage(4000)->create(['valid_from' => '2027-01-01']);
    MarkupRule::factory()->percentage(3000)->create(['valid_until' => '2026-10-31']);

    expect(priceFor('1000000')->margin()->isZero())->toBeTrue();
});

it('applies fixed markups per passenger, night and booking', function (MarkupKind $kind, string $expected): void {
    MarkupRule::factory()->fixed($kind, 5000000, 'COP')->create();

    expect(amountOf(priceFor('1000000'), PriceComponentType::Markup))->toBe($expected);
})->with([
    'per passenger (2)' => [MarkupKind::FixedPerPassenger, '100000.00'],
    'per night (3)' => [MarkupKind::FixedPerNight, '150000.00'],
    'per booking' => [MarkupKind::FixedPerBooking, '50000.00'],
]);

it('guarantees the minimum margin', function (): void {
    MarkupRule::factory()->fixed(MarkupKind::FixedPerBooking, 1000000, 'COP')->create(['min_margin_basis_points' => 500]);

    expect(amountOf(priceFor('1000000'), PriceComponentType::Markup))->toBe('50000.00');
});

it('adds service fees per passenger and per booking, taxed as agency income', function (): void {
    $this->seed(TaxReferenceSeeder::class);
    FeeRule::query()->create(['name' => 'Gestión', 'basis' => FeeBasis::PerBooking, 'amount_minor' => 3000000, 'currency' => 'COP', 'valid_from' => '2020-01-01', 'is_active' => true]);
    FeeRule::query()->create(['name' => 'Emisión', 'product_type' => ProductType::Flight, 'basis' => FeeBasis::PerPassenger, 'amount_minor' => 2000000, 'currency' => 'COP', 'valid_from' => '2020-01-01', 'is_active' => true]);

    $hotel = priceFor('1000000');
    $flight = priceFor('1000000', overrides: ['productType' => ProductType::Flight]);

    expect(amountOf($hotel, PriceComponentType::ServiceFee))->toBe('30000.00')
        ->and(amountOf($hotel, PriceComponentType::Tax))->toBe('5700.00')
        ->and(amountOf($flight, PriceComponentType::ServiceFee))->toBe('70000.00');
});

it('exempts product types from a tax', function (): void {
    TaxRule::query()->create(['name' => 'IVA', 'rate_basis_points' => 1900, 'exempt_product_types' => [ProductType::Flight->value], 'valid_from' => '2017-01-01', 'is_active' => true]);
    MarkupRule::factory()->percentage(1000)->create();

    expect(priceFor('1000000', overrides: ['productType' => ProductType::Flight])->totalOf(PriceComponentType::Tax)->isZero())->toBeTrue()
        ->and(amountOf(priceFor('1000000'), PriceComponentType::Tax))->toBe('19000.00');
});

it('converts a foreign net and fixed amounts to the sale currency and records the rate', function (): void {
    config(['travel.pricing.fx_spread_basis_points' => 100]);
    app(RecordExchangeRateAction::class)->execute('USD', 'COP', BigDecimal::of('4000'), ExchangeRateSource::Official, CarbonImmutable::parse('2026-11-09'));
    MarkupRule::factory()->fixed(MarkupKind::FixedPerBooking, 1000, 'USD')->create();

    $price = priceFor('500.00', 'USD');

    expect(amountOf($price, PriceComponentType::SupplierNet))->toBe('2020000.00')
        ->and(amountOf($price, PriceComponentType::Markup))->toBe('40400.00')
        ->and($price->exchangeRate?->rateDate->toDateString())->toBe('2026-11-09')
        ->and((string) $price->exchangeRate?->rate)->toBe('4040.00000000');
});

it('fails when there is no exchange rate for a foreign net', function (): void {
    priceFor('100', 'EUR');
})->throws(App\Modules\Pricing\Exceptions\ExchangeRateUnavailable::class);

it('labels pricing enums', function (): void {
    foreach ([...MarkupKind::cases(), ...FeeBasis::cases(), ...PriceComponentType::cases(), ...ExchangeRateSource::cases()] as $case) {
        expect($case->label())->not->toStartWith('pricing.');
    }

    expect(MarkupKind::FixedPerNight->isFixed())->toBeTrue()
        ->and(MarkupKind::Percentage->isFixed())->toBeFalse();
});
