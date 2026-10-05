<?php

declare(strict_types=1);

use App\Modules\Finance\Livewire\CashRegisterScreen;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Finance\Livewire\ProfitabilityScreen;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function (): void {
        Route::get('/payables', PayablesIndex::class)->name('payables');
        Route::get('/cash', CashRegisterScreen::class)->name('cash');
        Route::get('/profitability', ProfitabilityScreen::class)->name('profitability');
    });
