<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Contracts\ExchangeRates;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceBreakdown;
use App\Modules\Pricing\Data\PriceComponent;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Pricing\Enums\FeeBasis;
use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Pricing\Enums\PriceComponentType;
use App\Modules\Pricing\Models\FeeRule;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\ValueObjects\Percentage;
use Brick\Money\Money;

/**
 * Pipeline de precio (pricing-engine):
 * neto convertido → markup de la regla más específica (con margen mínimo) → fees → IVA sobre el ingreso de la agencia.
 */
final readonly class RuleBasedPriceCalculator implements PriceCalculator
{
    public function __construct(
        private PricingRuleRepository $rules,
        private ExchangeRates $exchangeRates,
    ) {}

    public function calculate(PriceRequest $request): PriceBreakdown
    {
        [$net, $quote] = $this->exchangeRates->convert($request->supplierNet, $request->saleCurrency, $request->serviceDate);
        $exchange = $request->supplierNet->getCurrency()->is($request->saleCurrency) ? null : $quote;

        $components = [new PriceComponent(PriceComponentType::SupplierNet, $net, __('pricing.component.supplier_net'))];

        $rule = $this->rules->markupFor($request);
        $markup = $rule instanceof MarkupRule ? $this->markup($rule, $net, $request) : Money::zero($net->getCurrency());
        $components[] = new PriceComponent(PriceComponentType::Markup, $markup, $rule->name ?? __('pricing.no_markup_rule'));

        foreach ($this->rules->feesFor($request) as $fee) {
            $components[] = new PriceComponent(PriceComponentType::ServiceFee, $this->fee($fee, $request), $fee->name);
        }

        $agencyIncome = array_reduce(
            array_filter($components, static fn(PriceComponent $component): bool => $component->type->isAgencyIncome()),
            static fn(Money $carry, PriceComponent $component): Money => $carry->plus($component->amount),
            Money::zero($net->getCurrency()),
        );

        foreach ($this->rules->taxesFor($request) as $tax) {
            $components[] = new PriceComponent(PriceComponentType::Tax, Percentage::fromBasisPoints($tax->rate_basis_points)->applyTo($agencyIncome), $tax->name);
        }

        return new PriceBreakdown($components, $exchange);
    }

    private function markup(MarkupRule $rule, Money $net, PriceRequest $request): Money
    {
        $markup = match ($rule->kind) {
            MarkupKind::Percentage => Percentage::fromBasisPoints((int) $rule->rate_basis_points)->applyTo($net),
            MarkupKind::FixedPerPassenger => $this->fixed($rule, $request)->multipliedBy($request->passengers),
            MarkupKind::FixedPerNight => $this->fixed($rule, $request)->multipliedBy(max($request->nights, 1)),
            MarkupKind::FixedPerBooking => $this->fixed($rule, $request),
        };

        if ($rule->min_margin_basis_points === null) {
            return $markup;
        }

        $minimum = Percentage::fromBasisPoints($rule->min_margin_basis_points)->applyTo($net);

        return $markup->isLessThan($minimum) ? $minimum : $markup;
    }

    /** Montos fijos en otra moneda se convierten a la moneda de venta con la tasa del día del servicio. */
    private function fixed(MarkupRule $rule, PriceRequest $request): Money
    {
        return $this->exchangeRates->convert(Money::ofMinor((int) $rule->amount_minor, (string) $rule->currency), $request->saleCurrency, $request->serviceDate)[0];
    }

    private function fee(FeeRule $fee, PriceRequest $request): Money
    {
        $amount = $this->exchangeRates->convert($fee->amount(), $request->saleCurrency, $request->serviceDate)[0];

        return $fee->basis === FeeBasis::PerPassenger ? $amount->multipliedBy($request->passengers) : $amount;
    }
}
