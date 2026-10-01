<?php

declare(strict_types=1);

use App\Modules\Catalog\Livewire\CatalogIndex;
use App\Modules\Catalog\Livewire\ProductForm;
use App\Modules\Catalog\Livewire\ProductShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('catalog')
    ->name('catalog.')
    ->group(function (): void {
        Route::get('/', CatalogIndex::class)->name('index');
        Route::get('/create', ProductForm::class)->name('create');
        Route::get('/{product}', ProductShow::class)->name('show');
        Route::get('/{product}/edit', ProductForm::class)->name('edit');
    });
