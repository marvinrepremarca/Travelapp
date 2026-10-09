<?php

declare(strict_types=1);

use App\Modules\Bookings\Http\Controllers\BookingPdfController;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Livewire\BookingsIndex;
use App\Modules\Bookings\Livewire\ConvertQuote;
use App\Modules\Bookings\Livewire\DirectBookingForm;
use App\Modules\Bookings\Livewire\DirectItemForm;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('bookings')
    ->name('bookings.')
    ->group(function (): void {
        Route::get('/', BookingsIndex::class)->name('index');
        // Expediente directo, sin cotización (ADR-0007).
        Route::get('/create', DirectBookingForm::class)->name('create');
        Route::get('/{booking}/items/create', DirectItemForm::class)->name('items.create');
        // Convertir necesita también la capacidad de Cotizaciones.
        Route::get('/from-quote/{quote}', ConvertQuote::class)->middleware(Capabilities::middleware(Capability::Quoting))->name('from-quote');
        Route::get('/{booking}', BookingShow::class)->name('show');
        Route::get('/{booking}/itinerary.pdf', [BookingPdfController::class, 'itinerary'])->name('itinerary');
        Route::get('/{booking}/items/{item}/voucher.pdf', [BookingPdfController::class, 'voucher'])->name('voucher');
    });
