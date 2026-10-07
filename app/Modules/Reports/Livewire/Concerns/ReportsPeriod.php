<?php

declare(strict_types=1);

namespace App\Modules\Reports\Livewire\Concerns;

use App\Modules\Identity\Models\User;
use App\Modules\Reports\Data\AgingBuckets;
use App\Modules\Reports\Data\Period;
use App\Modules\Reports\Data\SalesFigures;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/** Mes del reporte (en la URL), usuario actual y marcador de carga diferida comunes a los tableros. */
trait ReportsPeriod
{
    private const PIE_PERCENT = 100;

    private const PIE_SHARE_SCALE = 1;

    #[Url(except: '')]
    public string $month = '';

    public function placeholder(): View
    {
        return view('reports::livewire.placeholder');
    }

    protected function period(): Period
    {
        return Period::month($this->month, CarbonImmutable::now());
    }

    protected function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config()->string('travel.agency.timezone'))->startOfDay();
    }

    /**
     * Serie diaria para el gráfico.
     *
     * @param  array<int, int>  $daily
     * @return list<array{label: string, value: int, display: string}>
     */
    protected function chartItems(array $daily, string $currency, MoneyPresenter $presenter): array
    {
        $items = [];
        foreach ($daily as $day => $minor) {
            $items[] = ['label' => (string) $day, 'value' => $minor, 'display' => $presenter->format(Money::ofMinor($minor, $currency))];
        }

        return $items;
    }

    /**
     * Porciones de una torta en dinero: etiqueta → importe (misma moneda).
     *
     * @param  array<string, Money>  $slices
     * @return list<array{label: string, value: int, display: string, share: string}>
     */
    protected function moneyPie(array $slices, MoneyPresenter $presenter): array
    {
        $items = [];
        foreach ($slices as $label => $amount) {
            $items[] = ['label' => $label, 'value' => $amount->getMinorAmount()->toInt(), 'display' => $presenter->format($amount)];
        }

        return $this->withShares($items);
    }

    /**
     * Ventas de un desglose con su nombre visible, de mayor a menor.
     *
     * @param  array<int|string, SalesFigures>  $breakdown
     * @param  array<int|string, string>  $names
     * @return array<string, Money>
     */
    protected function salesBy(array $breakdown, array $names): array
    {
        $slices = [];
        foreach ($breakdown as $key => $figures) {
            $slices[$names[$key] ?? (string) $key] = $figures->sale;
        }
        uasort($slices, static fn(Money $left, Money $right): int => $right->compareTo($left));

        return $slices;
    }

    /**
     * Saldos por vencimiento como porciones.
     *
     * @return array<string, Money>
     */
    protected function agingSlices(AgingBuckets $buckets): array
    {
        return [
            __('reports.aging.overdue') => $buckets->overdue,
            __('reports.aging.due_soon', ['days' => config()->integer('travel.reports.due_soon_days')]) => $buckets->dueSoon,
            __('reports.aging.later') => $buckets->later,
        ];
    }

    /**
     * Porciones de una torta en conteos: etiqueta → cantidad.
     *
     * @param  array<string, int>  $slices
     * @return list<array{label: string, value: int, display: string, share: string}>
     */
    protected function countPie(array $slices): array
    {
        $items = [];
        foreach ($slices as $label => $count) {
            $items[] = ['label' => $label, 'value' => $count, 'display' => (string) $count];
        }

        return $this->withShares($items);
    }

    /**
     * @param  list<array{label: string, value: int, display: string}>  $items
     * @return list<array{label: string, value: int, display: string, share: string}>
     */
    private function withShares(array $items): array
    {
        $total = array_sum(array_map(static fn(array $item): int => max(0, $item['value']), $items));

        return array_map(static fn(array $item): array => $item + [
            'share' => $total === 0 ? '0' : (string) BigDecimal::of(max(0, $item['value']))->multipliedBy(self::PIE_PERCENT)->dividedBy($total, self::PIE_SHARE_SCALE, RoundingMode::HALF_UP),
        ], $items);
    }

    protected function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
