<?php

declare(strict_types=1);

use App\Modules\Crm\Livewire\CustomerForm;
use App\Modules\Crm\Livewire\CustomerShow;
use App\Modules\Crm\Livewire\CustomersIndex;
use App\Modules\Crm\Livewire\LeadForm;
use App\Modules\Crm\Livewire\LeadsBoard;
use App\Modules\Crm\Livewire\LeadShow;
use App\Modules\Crm\Livewire\TravelerForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('crm')
    ->name('crm.')
    ->group(function (): void {
        Route::get('/customers', CustomersIndex::class)->name('customers.index');
        Route::get('/customers/create', CustomerForm::class)->name('customers.create');
        Route::get('/customers/{customer}', CustomerShow::class)->name('customers.show');
        Route::get('/customers/{customer}/edit', CustomerForm::class)->name('customers.edit');
        Route::get('/customers/{customer}/travelers/create', TravelerForm::class)->name('customers.travelers.create');
        Route::get('/customers/{customer}/travelers/{traveler}/edit', TravelerForm::class)->name('customers.travelers.edit');

        Route::get('/leads', LeadsBoard::class)->name('leads.index');
        Route::get('/leads/create', LeadForm::class)->name('leads.create');
        Route::get('/leads/{lead}', LeadShow::class)->name('leads.show');
        Route::get('/leads/{lead}/edit', LeadForm::class)->name('leads.edit');
    });
