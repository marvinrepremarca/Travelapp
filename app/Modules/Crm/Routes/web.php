<?php

declare(strict_types=1);

use App\Modules\Crm\Livewire\CustomerForm;
use App\Modules\Crm\Livewire\CustomerShow;
use App\Modules\Crm\Livewire\CustomersIndex;
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
    });
