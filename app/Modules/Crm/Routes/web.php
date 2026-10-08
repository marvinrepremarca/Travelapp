<?php

declare(strict_types=1);

use App\Modules\Crm\Livewire\LeadForm;
use App\Modules\Crm\Livewire\LeadsBoard;
use App\Modules\Crm\Livewire\LeadShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function (): void {
        Route::get('/leads', LeadsBoard::class)->name('leads.index');
        Route::get('/leads/create', LeadForm::class)->name('leads.create');
        Route::get('/leads/{lead}', LeadShow::class)->name('leads.show');
        Route::get('/leads/{lead}/edit', LeadForm::class)->name('leads.edit');
    });
