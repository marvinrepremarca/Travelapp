<?php

declare(strict_types=1);

use App\Modules\Pricing\Actions\FetchOfficialRateAction;
use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Contracts\ExchangeRates;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use App\Modules\Pricing\Exceptions\InvalidExchangeRate;
use App\Modules\Pricing\Livewire\ExchangeRatesManager;
use App\Modules\Pricing\Models\ExchangeRate;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function recordRate(string $base, string $quote, string $rate, string $date, ExchangeRateSource $source = ExchangeRateSource::Official): ExchangeRate
{
    return app(RecordExchangeRateAction::class)->execute($base, $quote, BigDecimal::of($rate), $source, CarbonImmutable::parse($date));
}

it('stores the weekend TRM for every day it covers', function (): void {
    Http::fake(['www.datos.gov.co/*' => Http::response((string) file_get_contents(base_path('tests/Fixtures/Suppliers/DatosGov/trm_weekend_ok.json')))]);

    $saved = app(FetchOfficialRateAction::class)->execute(CarbonImmutable::parse('2026-09-27'));

    expect(array_map(static fn(ExchangeRate $rate): string => $rate->valid_on->toDateString(), $saved))->toBe(['2026-09-26', '2026-09-27', '2026-09-28'])
        ->and(ExchangeRate::query()->count())->toBe(3);
});

it('runs the scheduled command and reports a source failure', function (): void {
    config(['suppliers.providers.datos_gov_trm.retries' => 1]);
    Http::fake(['www.datos.gov.co/*' => Http::sequence()
        ->push((string) file_get_contents(base_path('tests/Fixtures/Suppliers/DatosGov/trm_ok.json')))
        ->push('down', 503)]);
    $this->artisan('pricing:fetch-official-rate', ['date' => '2026-10-01'])->assertSuccessful();
    $this->artisan('pricing:fetch-official-rate', ['date' => '2026-10-02'])->assertFailed();

    $commands = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->map(fn($event): string => (string) $event->command);
    expect($commands->filter(fn(string $command): bool => str_contains($command, 'pricing:fetch-official-rate'))->count())->toBe(3);
});

it('converts with the latest rate on or before the date plus the agency spread', function (): void {
    config(['travel.pricing.fx_spread_basis_points' => 200]);
    recordRate('USD', 'COP', '4000', '2026-10-01');
    recordRate('USD', 'COP', '4100', '2026-10-05');

    [$cop, $quote] = app(ExchangeRates::class)->convert(Money::of('100.00', 'USD'), 'COP', CarbonImmutable::parse('2026-10-03'));

    expect((string) $cop->getAmount())->toBe('408000.00')
        ->and((string) $quote->officialRate)->toBe('4000.00000000')
        ->and((string) $quote->rate)->toBe('4080.00000000')
        ->and($quote->spreadBasisPoints)->toBe(200)
        ->and($quote->rateDate->toDateString())->toBe('2026-10-01');
});

it('prefers a manual rate over the official one on the same day', function (): void {
    recordRate('USD', 'COP', '4000', '2026-10-01');
    recordRate('USD', 'COP', '3950', '2026-10-01', ExchangeRateSource::Manual);

    $quote = app(ExchangeRates::class)->quote('USD', 'COP', CarbonImmutable::parse('2026-10-01'));

    expect((string) $quote->officialRate)->toBe('3950.00000000')
        ->and($quote->source)->toBe(ExchangeRateSource::Manual);
});

it('converts in the inverse direction and between equal currencies', function (): void {
    recordRate('USD', 'COP', '4000', '2026-10-01');

    [$usd] = app(ExchangeRates::class)->convert(Money::of('400000', 'COP'), 'USD', CarbonImmutable::parse('2026-10-01'));
    [$same, $identity] = app(ExchangeRates::class)->convert(Money::of('10', 'USD'), 'usd', CarbonImmutable::parse('2026-10-01'));

    expect((string) $usd->getAmount())->toBe('100.00')
        ->and((string) $same->getAmount())->toBe('10.00')
        ->and((string) $identity->rate)->toBe('1');
});

it('fails when no rate exists for the pair or date', function (): void {
    recordRate('USD', 'COP', '4000', '2026-10-05');

    app(ExchangeRates::class)->quote('USD', 'COP', CarbonImmutable::parse('2026-10-01'));
})->throws(ExchangeRateUnavailable::class);

it('rejects invalid rates', function (string $base, string $quote, string $rate): void {
    recordRate($base, $quote, $rate, '2026-10-01');
})->with([
    'same currency' => ['USD', 'USD', '1'],
    'zero' => ['USD', 'COP', '0'],
    'negative' => ['EUR', 'COP', '-1'],
])->throws(InvalidExchangeRate::class);

it('lets finance record rates from the screen and everyone browse them', function (): void {
    actingAs(financeUser());

    Livewire::test(ExchangeRatesManager::class)
        ->set('base', 'EUR')
        ->set('quote', 'COP')
        ->set('rate', '4512.75')
        ->set('valid_on', '2026-10-01')
        ->call('record')
        ->assertHasNoErrors()
        ->assertSee('4512.75000000');

    expect(ExchangeRate::query()->sole()->source)->toBe(ExchangeRateSource::Manual);

    actingAs(agent())->get(route('pricing.rates'))->assertOk()->assertSee('4512.75000000')->assertDontSee(__('pricing.rates.record_title'));
});

it('validates the manual rate and forbids non finance users', function (): void {
    actingAs(financeUser());
    Livewire::test(ExchangeRatesManager::class)->set('rate', 'abc')->set('base', 'COP')->call('record')->assertHasErrors(['rate', 'base']);

    actingAs(agent());
    Livewire::test(ExchangeRatesManager::class)->set('rate', '1')->set('base', 'EUR')->call('record')->assertForbidden();
});

it('downloads the official rate from the screen and shows source failures', function (): void {
    actingAs(financeUser());
    config(['suppliers.providers.datos_gov_trm.retries' => 1]);

    Http::fake(['www.datos.gov.co/*' => Http::sequence()
        ->push((string) file_get_contents(base_path('tests/Fixtures/Suppliers/DatosGov/trm_ok.json')))
        ->push('', 500)]);
    Livewire::test(ExchangeRatesManager::class)->set('valid_on', '2026-10-01')->call('fetchOfficial')->assertHasNoErrors();
    expect(ExchangeRate::query()->sole()->rate)->toBe('3312.84000000');

    Livewire::test(ExchangeRatesManager::class)->set('valid_on', '2026-10-02')->call('fetchOfficial')
        ->assertHasErrors(['rate' => __('pricing.errors.source_failed')]);
});

it('shows an empty state and requires login', function (): void {
    get(route('pricing.rates'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('pricing.rates'))->assertSee(__('pricing.rates.empty_title'));
});
