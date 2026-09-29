<?php

declare(strict_types=1);

use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Shared\Money\MoneySplitter;
use App\Modules\Shared\ValueObjects\Percentage;
use Brick\Money\Money;

function presenter(): MoneyPresenter
{
    return app(MoneyPresenter::class);
}

it('rounds half up to the presentation decimals of each currency', function (string $amount, string $currency, string $expected): void {
    $money = Money::of($amount, $currency, new Brick\Money\Context\CustomContext(4));

    expect((string) presenter()->round($money)->getAmount())->toBe($expected);
})->with([
    'usd rounds up at half' => ['10.005', 'USD', '10.01'],
    'usd rounds down below half' => ['10.0049', 'USD', '10.00'],
    'eur keeps two decimals' => ['99.995', 'EUR', '100.00'],
    'cop has no decimals by configuration' => ['50000.50', 'COP', '50001'],
    'cop below half' => ['50000.49', 'COP', '50000'],
    'jpy has no decimals by iso' => ['1500.5', 'JPY', '1501'],
]);

it('reads presentation decimals from configuration before iso', function (): void {
    config(['travel.money.presentation_decimals' => ['COP' => 2]]);

    expect(presenter()->decimalsFor('COP'))->toBe(2)
        ->and(presenter()->decimalsFor('USD'))->toBe(2)
        ->and(presenter()->decimalsFor('JPY'))->toBe(0);
});

it('formats amounts for the agency locale', function (string $amount, string $currency, string $expected): void {
    $formatted = presenter()->format(Money::of($amount, $currency));

    expect(preg_replace('/\s/u', ' ', $formatted))->toBe($expected);
})->with([
    'cop without decimals' => ['1234567', 'COP', '$ 1.234.567'],
    'usd with decimals' => ['1234.5', 'USD', 'US$ 1.234,50'],
]);

it('formats with an explicit locale', function (): void {
    expect(presenter()->format(Money::of('1234.5', 'USD'), 'en_US'))->toBe('$1,234.50');
});

it('splits a total equally without losing cents', function (string $total, int $parts, array $expected): void {
    $shares = MoneySplitter::equally(Money::of($total, 'USD'), $parts);

    expect(array_map(static fn(Money $share): string => (string) $share->getAmount(), $shares))->toBe($expected)
        ->and((string) Money::total(...$shares)->getAmount())->toBe(Money::of($total, 'USD')->getAmount()->__toString());
})->with([
    'exact' => ['300.00', 3, ['100.00', '100.00', '100.00']],
    'remainder to first parts' => ['100.00', 3, ['33.34', '33.33', '33.33']],
    'single part' => ['99.99', 1, ['99.99']],
]);

it('splits by ratios such as adult and child fares', function (): void {
    $shares = MoneySplitter::byRatios(Money::of('1000.00', 'USD'), [100, 100, 70]);

    expect(array_map(static fn(Money $share): string => (string) $share->getAmount(), $shares))->toBe(['370.38', '370.37', '259.25'])
        ->and((string) Money::total(...$shares)->getAmount())->toBe('1000.00');
});

it('rejects invalid splits', function (Closure $split): void {
    expect($split)->toThrow(InvalidArgumentException::class);
})->with([
    'zero parts' => [fn(): array => MoneySplitter::equally(Money::of('10', 'USD'), 0)],
    'no ratios' => [fn(): array => MoneySplitter::byRatios(Money::of('10', 'USD'), [])],
    'zero ratios' => [fn(): array => MoneySplitter::byRatios(Money::of('10', 'USD'), [0, 0])],
    'negative ratio' => [fn(): array => MoneySplitter::byRatios(Money::of('10', 'USD'), [5, -1])],
]);

it('applies exact percentages without floats', function (string $percent, string $amount, string $currency, string $expected): void {
    expect((string) Percentage::fromString($percent)->applyTo(Money::of($amount, $currency))->getAmount())->toBe($expected);
})->with([
    'vat 19 %' => ['19', '100.00', 'USD', '19.00'],
    'fractional percent' => ['12.5', '80.00', 'USD', '10.00'],
    'rounds half up' => ['10', '0.05', 'USD', '0.01'],
    'cop amount' => ['19', '250000', 'COP', '47500.00'],
]);

it('stores percentages as basis points', function (): void {
    expect(Percentage::fromString('12.345')->basisPoints)->toBe(1235)
        ->and(Percentage::fromBasisPoints(1900)->toFraction()->__toString())->toBe('0.1900')
        ->and(Percentage::fromBasisPoints(0)->isZero())->toBeTrue()
        ->and(Percentage::fromString('1')->isZero())->toBeFalse();
});
