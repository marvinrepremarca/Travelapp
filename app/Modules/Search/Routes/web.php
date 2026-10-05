<?php

declare(strict_types=1);

use App\Modules\Search\Livewire\FlightSearch;
use App\Modules\Search\Livewire\HotelSearch;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'throttle:search'])
    ->prefix('search')
    ->name('search.')
    ->group(function (): void {
        Route::get('/flights', FlightSearch::class)->name('flights');
        Route::get('/hotels', HotelSearch::class)->name('hotels');
    });
