<?php

declare(strict_types=1);

use App\Modules\Suppliers\Livewire\SupplierForm;
use App\Modules\Suppliers\Livewire\SupplierShow;
use App\Modules\Suppliers\Livewire\SuppliersIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('suppliers')
    ->name('suppliers.')
    ->group(function (): void {
        Route::get('/', SuppliersIndex::class)->name('index');
        Route::get('/create', SupplierForm::class)->name('create');
        Route::get('/{supplier}', SupplierShow::class)->name('show');
        Route::get('/{supplier}/edit', SupplierForm::class)->name('edit');
    });
