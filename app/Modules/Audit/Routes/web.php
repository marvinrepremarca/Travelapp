<?php

declare(strict_types=1);

use App\Modules\Audit\Livewire\AuditLogIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('audit')
    ->name('audit.')
    ->group(function (): void {
        Route::get('/', AuditLogIndex::class)->name('index');
    });
