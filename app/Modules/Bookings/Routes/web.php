<?php

declare(strict_types=1);

use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Livewire\BookingsIndex;
use App\Modules\Bookings\Livewire\ConvertQuote;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('bookings')
    ->name('bookings.')
    ->group(function (): void {
        Route::get('/', BookingsIndex::class)->name('index');
        Route::get('/from-quote/{quote}', ConvertQuote::class)->name('from-quote');
        Route::get('/{booking}', BookingShow::class)->name('show');
    });
