<?php

declare(strict_types=1);

use App\Modules\Organization\Livewire\AgencyProfileForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('organization')
    ->name('organization.')
    ->group(function (): void {
        Route::get('/agency', AgencyProfileForm::class)->name('agency');
    });
