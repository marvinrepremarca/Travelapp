<?php

declare(strict_types=1);

use App\Modules\Communications\Http\Controllers\MessagingWebhookController;
use App\Modules\Communications\Livewire\ConversationsInbox;
use App\Modules\Communications\Providers\CommunicationsServiceProvider;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('communications')
    ->name('communications.')
    ->group(function (): void {
        Route::get('/conversations', ConversationsInbox::class)->name('inbox');
    });

// Webhook del proveedor de mensajería: sin grupo web (sin sesión ni CSRF); la firma autentica y el limitador protege.
// Sigue recibiendo con Mensajería apagada: ningún mensaje de un cliente se pierde (ADR-0007).
Route::post('webhooks/messaging/{channel}', MessagingWebhookController::class)
    ->middleware('throttle:' . CommunicationsServiceProvider::WEBHOOK_LIMITER)
    ->withoutMiddleware(Capabilities::middleware(Capability::Messaging))
    ->name('communications.webhook');
