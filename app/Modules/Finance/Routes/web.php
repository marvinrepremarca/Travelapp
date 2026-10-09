<?php

declare(strict_types=1);

use App\Modules\Finance\Livewire\BankAccountForm;
use App\Modules\Finance\Livewire\BankAccountsIndex;
use App\Modules\Finance\Livewire\CashRegisterScreen;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Finance\Livewire\ProfitabilityScreen;
use App\Modules\Finance\Livewire\ReconciliationScreen;
use App\Modules\Finance\Livewire\RevenueScreen;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function (): void {
        Route::get('/payables', PayablesIndex::class)->name('payables');
        Route::get('/revenue', RevenueScreen::class)->name('revenue');
        Route::get('/cash', CashRegisterScreen::class)->name('cash');
        Route::get('/profitability', ProfitabilityScreen::class)->name('profitability');
        Route::get('/bank-accounts', BankAccountsIndex::class)->name('bank-accounts');
        Route::get('/bank-accounts/create', BankAccountForm::class)->name('bank-accounts.create');
        Route::get('/bank-accounts/{account}/edit', BankAccountForm::class)->name('bank-accounts.edit');
        Route::get('/reconciliation', ReconciliationScreen::class)->name('reconciliation');
    });
