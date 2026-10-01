<?php

declare(strict_types=1);

use App\Modules\Quotes\Livewire\QuoteCreate;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Livewire\QuotesIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('quotes')
    ->name('quotes.')
    ->group(function (): void {
        Route::get('/', QuotesIndex::class)->name('index');
        Route::get('/create', QuoteCreate::class)->name('create');
        Route::get('/{quote}', QuoteShow::class)->name('show');
    });
