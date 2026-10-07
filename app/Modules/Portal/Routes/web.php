<?php

declare(strict_types=1);

use App\Modules\Portal\Http\Controllers\PortalDocumentsController;
use App\Modules\Portal\Livewire\RequestAccess;
use App\Modules\Portal\Livewire\ShopIndex;
use App\Modules\Portal\Livewire\ShopProductPage;
use App\Modules\Portal\Livewire\TripPortal;
use App\Modules\Portal\Providers\PortalServiceProvider;
use Illuminate\Support\Facades\Route;

// Portal del viajero: sin sesión; el acceso lo da el enlace firmado y vigente ("enlace mágico").
Route::middleware(['web', 'signed', 'throttle:' . PortalServiceProvider::LINK_LIMITER])
    ->prefix('my-trip/{booking}')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', TripPortal::class)->name('trip');
        Route::get('/itinerary.pdf', [PortalDocumentsController::class, 'itinerary'])->name('itinerary');
        Route::get('/vouchers/{item}.pdf', [PortalDocumentsController::class, 'voucher'])->name('voucher');
    });

Route::middleware(['web', 'throttle:' . PortalServiceProvider::LINK_LIMITER])
    ->get('my-trip', RequestAccess::class)
    ->name('portal.access');

// Tienda B2C pública: productos propios con salidas abiertas; las solicitudes llegan como leads a un asesor.
Route::middleware(['web', 'throttle:' . PortalServiceProvider::LINK_LIMITER])
    ->prefix('shop')
    ->name('portal.shop')
    ->group(function (): void {
        Route::get('/', ShopIndex::class);
        Route::get('/{product}', ShopProductPage::class)->name('.product');
    });
