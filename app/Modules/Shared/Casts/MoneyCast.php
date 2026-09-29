<?php

declare(strict_types=1);

namespace App\Modules\Shared\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Guarda un Money en dos columnas: importe en unidades menores (entero) y moneda ISO 4217.
 * Uso: `'sale_amount' => MoneyCast::class.':sale_amount_minor,sale_currency'`.
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
final readonly class MoneyCast implements CastsAttributes
{
    public function __construct(
        private string $amountColumn,
        private string $currencyColumn,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $amount = $attributes[$this->amountColumn] ?? null;
        $currency = $attributes[$this->currencyColumn] ?? null;

        if ($amount === null || ! is_string($currency) || $currency === '') {
            return null;
        }

        return Money::ofMinor((string) $amount, $currency);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|string|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$this->amountColumn => null, $this->currencyColumn => null];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException(__('shared.errors.money_expected', ['key' => $key]));
        }

        return [
            $this->amountColumn => $value->getMinorAmount()->toInt(),
            $this->currencyColumn => $value->getCurrency()->getCurrencyCode(),
        ];
    }
}
