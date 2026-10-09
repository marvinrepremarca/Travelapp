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

    /**
     * Foto del desglose que se congela en la cotización y en la reserva (mismo formato en ambas capacidades).
     *
     * @return array{currency: string, components: list<array{type: string, description: string, amount_minor: int}>, exchange_rate: array{from: string, to: string, rate: string, official_rate: string, spread_basis_points: int, source: string, rate_date: string}|null}
     */
    public function snapshot(string $saleCurrency): array
    {
        $rate = $this->exchangeRate;

        return [
            'currency' => $saleCurrency,
            'components' => array_map(static fn(PriceComponent $component): array => [
                'type' => $component->type->value,
                'description' => $component->description,
                'amount_minor' => $component->amount->getMinorAmount()->toInt(),
            ], $this->components),
            'exchange_rate' => $rate instanceof ExchangeRateQuote ? [
                'from' => $rate->from,
                'to' => $rate->to,
                'rate' => (string) $rate->rate,
                'official_rate' => (string) $rate->officialRate,
                'spread_basis_points' => $rate->spreadBasisPoints,
                'source' => $rate->source->value,
                'rate_date' => $rate->rateDate->toDateString(),
            ] : null,
        ];
    }

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
