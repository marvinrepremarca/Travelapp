<?php

declare(strict_types=1);

use App\Modules\Integrations\Http\Controllers\FakeCheckoutController;
use Illuminate\Support\Facades\Route;

// Página de pago de la pasarela simulada: solo con firma vigente (el link la genera el adaptador Fake).
Route::middleware(['web', 'signed'])
    ->prefix('fake-checkout')
    ->name('integrations.fake-checkout')
    ->group(function (): void {
        Route::get('/', [FakeCheckoutController::class, 'show']);
        Route::post('/complete', [FakeCheckoutController::class, 'complete'])->name('.complete');
    });
