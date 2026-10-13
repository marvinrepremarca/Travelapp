<?php

declare(strict_types=1);

use App\Modules\Pricing\Livewire\ExchangeRatesManager;
use App\Modules\Pricing\Livewire\PriceSimulator;
use App\Modules\Pricing\Livewire\PricingRulesManager;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('pricing')
    ->name('pricing.')
    ->group(function (): void {
        Route::get('/exchange-rates', ExchangeRatesManager::class)->middleware('can:' . Permission::PricingView->value)->name('rates');
        Route::get('/rules', PricingRulesManager::class)->name('rules');
        Route::get('/simulator', PriceSimulator::class)->middleware('can:' . Permission::PricingView->value)->name('simulator');
    });
