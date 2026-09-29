<?php

declare(strict_types=1);

use App\Modules\Organization\Livewire\AgencyProfileForm;
use App\Modules\Organization\Livewire\BranchesIndex;
use App\Modules\Organization\Livewire\BranchForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('organization')
    ->name('organization.')
    ->group(function (): void {
        Route::get('/agency', AgencyProfileForm::class)->name('agency');

        Route::get('/branches', BranchesIndex::class)->name('branches.index');
        Route::get('/branches/create', BranchForm::class)->name('branches.create');
        Route::get('/branches/{branch}/edit', BranchForm::class)->name('branches.edit');
    });
