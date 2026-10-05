<?php

declare(strict_types=1);

use App\Modules\Finance\Livewire\PayablesIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function (): void {
        Route::get('/payables', PayablesIndex::class)->name('payables');
    });
