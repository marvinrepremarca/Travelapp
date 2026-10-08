<?php

declare(strict_types=1);

use App\Modules\Customers\Livewire\CustomerForm;
use App\Modules\Customers\Livewire\CustomerShow;
use App\Modules\Customers\Livewire\CustomersIndex;
use App\Modules\Customers\Livewire\TravelerForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('customers')
    ->name('customers.')
    ->group(function (): void {
        Route::get('/', CustomersIndex::class)->name('index');
        Route::get('/create', CustomerForm::class)->name('create');
        Route::get('/{customer}', CustomerShow::class)->name('show');
        Route::get('/{customer}/edit', CustomerForm::class)->name('edit');
        Route::get('/{customer}/travelers/create', TravelerForm::class)->name('travelers.create');
        Route::get('/{customer}/travelers/{traveler}/edit', TravelerForm::class)->name('travelers.edit');
    });
