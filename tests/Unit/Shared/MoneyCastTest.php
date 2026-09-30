<?php

declare(strict_types=1);

use App\Modules\Shared\Casts\MoneyCast;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Model;

function moneyCast(): MoneyCast
{
    return new MoneyCast('amount_minor', 'currency');
}

function anyModel(): Model
{
    return new class extends Model {};
}

it('reads money from minor units and currency', function (int $minor, string $currency, string $expected): void {
    $money = moneyCast()->get(anyModel(), 'amount', null, ['amount_minor' => $minor, 'currency' => $currency]);

    expect($money)->toBeInstanceOf(Money::class)
        ->and((string) $money)->toBe($expected);
})->with([
    'usd has 2 decimals' => [12345, 'USD', 'USD 123.45'],
    'cop has 2 decimals in iso 4217' => [5000000, 'COP', 'COP 50000.00'],
    'jpy has no decimals' => [1500, 'JPY', 'JPY 1500'],
]);

it('reads null when amount or currency is missing', function (array $attributes): void {
    expect(moneyCast()->get(anyModel(), 'amount', null, $attributes))->toBeNull();
})->with([
    'no amount' => [['amount_minor' => null, 'currency' => 'USD']],
    'no currency' => [['amount_minor' => 100, 'currency' => null]],
    'empty currency' => [['amount_minor' => 100, 'currency' => '']],
]);

it('writes money as minor units and currency code', function (): void {
    $columns = moneyCast()->set(anyModel(), 'amount', Money::of('99.99', 'USD'), []);

    expect($columns)->toBe(['amount_minor' => 9999, 'currency' => 'USD']);
});

it('writes nulls when value is null', function (): void {
    expect(moneyCast()->set(anyModel(), 'amount', null, []))
        ->toBe(['amount_minor' => null, 'currency' => null]);
});

it('rejects values that are not money', function (): void {
    moneyCast()->set(anyModel(), 'amount', '99.99', []);
})->throws(InvalidArgumentException::class);

it('encrypts dates at rest and reads them back as immutable dates', function (): void {
    $cast = new App\Modules\Shared\Casts\EncryptedDate();
    $stored = $cast->set(anyModel(), 'birth_date', '1990-05-17 13:45:00', []);

    expect($stored)->not->toContain('1990')
        ->and($cast->get(anyModel(), 'birth_date', $stored, [])?->toDateString())->toBe('1990-05-17')
        ->and($cast->set(anyModel(), 'birth_date', new DateTimeImmutable('2001-02-03'), []))->toBeString()
        ->and($cast->set(anyModel(), 'birth_date', null, []))->toBeNull()
        ->and($cast->get(anyModel(), 'birth_date', null, []))->toBeNull();
});
