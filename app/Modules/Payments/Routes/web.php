<?php

declare(strict_types=1);

use App\Modules\Payments\Http\Controllers\GatewayWebhookController;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Providers\PaymentsServiceProvider;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('payments')
    ->name('payments.')
    ->group(function (): void {
        Route::get('/bookings/{booking}', BookingPayments::class)->name('booking');
    });

// Webhooks de pasarelas: sin grupo web (sin sesión ni CSRF); la firma autentica y el limitador protege.
Route::post('webhooks/payments/{gateway}', GatewayWebhookController::class)
    ->middleware('throttle:' . PaymentsServiceProvider::WEBHOOK_LIMITER)
    ->name('payments.webhook');
