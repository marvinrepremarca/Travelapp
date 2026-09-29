<?php

declare(strict_types=1);

use App\Http\Controllers\DesignSystemController;
use App\Modules\Shared\Health\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthCheckController::class)
    ->middleware('throttle:health')
    ->name('health');

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'dashboard')->name('dashboard');
    Route::get('/design-system', DesignSystemController::class)->name('design-system');
});
