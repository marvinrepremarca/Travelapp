<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

use App\Modules\Pricing\Enums\PriceComponentType;
use Brick\Money\Money;

/**
 * Precio de venta explicado componente por componente. El margen siempre se deriva
 * (markup + fees), nunca se digita. Este objeto se congela en la cotización y en la reserva.
 */
final readonly class PriceBreakdown
{
    /** @param list<PriceComponent> $components */
    public function __construct(
        public array $components,
        public ?ExchangeRateQuote $exchangeRate,
    ) {}

    public function total(): Money
    {
        return $this->sum(static fn(PriceComponent $component): bool => true);
    }

    public function margin(): Money
    {
        return $this->sum(static fn(PriceComponent $component): bool => $component->type->isAgencyIncome());
    }

    public function totalOf(PriceComponentType $type): Money
    {
        return $this->sum(static fn(PriceComponent $component): bool => $component->type === $type);
    }

    /** @param callable(PriceComponent): bool $filter */
    private function sum(callable $filter): Money
    {
        $currency = $this->components[0]->amount->getCurrency();
        $total = Money::zero($currency);

        foreach ($this->components as $component) {
            if ($filter($component)) {
                $total = $total->plus($component->amount);
            }
        }

        return $total;
    }
}
