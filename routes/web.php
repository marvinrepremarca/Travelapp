<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'dashboard')->name('dashboard');
});
