<?php

declare(strict_types=1);

namespace App\Modules\Reports\Livewire\Concerns;

use App\Modules\Identity\Models\User;
use App\Modules\Reports\Data\Period;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/** Mes del reporte (en la URL), usuario actual y marcador de carga diferida comunes a los tableros. */
trait ReportsPeriod
{
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
            $items[] = ['label' => (string) $day, 'value' => $minor, 'display' => $presenter->format(\Brick\Money\Money::ofMinor($minor, $currency))];
        }

        return $items;
    }

    protected function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
