<?php

declare(strict_types=1);

use App\Modules\Integrations\Adapters\DatosGov\DatosGovTrmSource;
use App\Modules\Integrations\Enums\RequestOutcome;
use App\Modules\Integrations\Exceptions\ProviderRequestFailed;
use App\Modules\Integrations\Models\SupplierRequest;
use App\Modules\Integrations\Support\ProviderHttpClient;
use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function trmFixture(string $name): string
{
    return (string) file_get_contents(base_path("tests/Fixtures/Suppliers/DatosGov/{$name}.json"));
}

function fakeTrm(mixed $response): void
{
    config(['suppliers.providers.datos_gov_trm.retries' => 1, 'suppliers.providers.datos_gov_trm.retry_sleep_ms' => 0]);
    Http::fake(['www.datos.gov.co/*' => $response]);
}

it('is the adapter bound to the official rate port', function (): void {
    expect(app(OfficialExchangeRateSource::class))->toBeInstanceOf(DatosGovTrmSource::class);
});

it('maps a weekday TRM to a domain rate and logs the call', function (): void {
    fakeTrm(Http::response(trmFixture('trm_ok')));

    $rate = app(OfficialExchangeRateSource::class)->rateOn(CarbonImmutable::parse('2026-10-01'));

    expect($rate->base)->toBe('USD')
        ->and($rate->quote)->toBe('COP')
        ->and((string) $rate->rate)->toBe('3312.84')
        ->and($rate->validFrom->toDateString())->toBe('2026-10-01')
        ->and($rate->validUntil->toDateString())->toBe('2026-10-01');

    $log = SupplierRequest::query()->sole();
    expect($log->provider)->toBe(DatosGovTrmSource::PROVIDER)
        ->and($log->outcome)->toBe(RequestOutcome::Success)
        ->and($log->http_status)->toBe(200);

    Http::assertSent(fn($request): bool => str_contains(urldecode($request->url()), "vigenciadesde <= '2026-10-01T00:00:00.000'")
        && $request->hasHeader('X-Correlation-Id'));
});

it('maps a weekend TRM covering several days', function (): void {
    fakeTrm(Http::response(trmFixture('trm_weekend_ok')));

    $rate = app(OfficialExchangeRateSource::class)->rateOn(CarbonImmutable::parse('2026-09-27'));

    expect($rate->validFrom->toDateString())->toBe('2026-09-26')
        ->and($rate->validUntil->toDateString())->toBe('2026-09-28');
});

it('fails cleanly when the source has no data or a malformed answer', function (string $fixture): void {
    fakeTrm(Http::response(trmFixture($fixture)));

    app(OfficialExchangeRateSource::class)->rateOn(CarbonImmutable::parse('2026-10-01'));
})->with(['trm_empty', 'trm_malformed'])->throws(ExchangeRateUnavailable::class);

it('translates server errors and timeouts and records the outcome', function (mixed $response, RequestOutcome $outcome): void {
    fakeTrm($response);

    expect(fn() => app(OfficialExchangeRateSource::class)->rateOn(CarbonImmutable::parse('2026-10-01')))
        ->toThrow(ExchangeRateUnavailable::class, __('pricing.errors.source_failed'));

    expect(SupplierRequest::query()->sole()->outcome)->toBe($outcome);
})->with([
    'server error' => [fn() => Http::response('error', 503), RequestOutcome::ServerError],
    'client error' => [fn() => Http::response('bad', 400), RequestOutcome::ClientError],
    'timeout' => [fn(): \Closure => fn() => throw new ConnectionException('timed out'), RequestOutcome::Timeout],
]);

it('refuses hosts outside the allow list', function (): void {
    Http::fake();

    app(ProviderHttpClient::class)->getJson(DatosGovTrmSource::PROVIDER, 'probe', 'https://evil.example.com/steal');
})->throws(ProviderRequestFailed::class);
