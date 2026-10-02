<?php

declare(strict_types=1);

use App\Modules\Quotes\Http\Controllers\QuotePdfController;
use App\Modules\Quotes\Livewire\PublicQuote;
use App\Modules\Quotes\Livewire\QuoteCreate;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Livewire\QuotesIndex;
use App\Modules\Quotes\Providers\QuotesServiceProvider;
use App\Modules\Quotes\Services\QuoteLinks;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('quotes')
    ->name('quotes.')
    ->group(function (): void {
        Route::get('/', QuotesIndex::class)->name('index');
        Route::get('/create', QuoteCreate::class)->name('create');
        Route::get('/{quote}', QuoteShow::class)->name('show');
        Route::get('/{quote}/versions/{version}/pdf', [QuotePdfController::class, 'internal'])->whereNumber('version')->name('pdf');
    });

// Enlace del cliente: sin sesión, solo con firma vigente y con límite de solicitudes por IP.
Route::middleware(['web', 'signed', 'throttle:' . QuotesServiceProvider::CUSTOMER_LINK_LIMITER])
    ->get('quote-link/{quote}/{version}', PublicQuote::class)
    ->whereNumber('version')
    ->name(QuoteLinks::ROUTE);

Route::middleware(['web', 'signed', 'throttle:' . QuotesServiceProvider::CUSTOMER_LINK_LIMITER])
    ->get('quote-link/{quote}/{version}/pdf', [QuotePdfController::class, 'customer'])
    ->whereNumber('version')
    ->name(QuoteLinks::PDF_ROUTE);
