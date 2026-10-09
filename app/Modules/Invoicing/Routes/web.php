<?php

declare(strict_types=1);

use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Livewire\InvoicesIndex;
use App\Modules\Invoicing\Livewire\ManualInvoiceForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('invoicing')
    ->name('invoicing.')
    ->group(function (): void {
        Route::get('/', InvoicesIndex::class)->name('index');
        Route::get('/create', ManualInvoiceForm::class)->name('create');
        Route::get('/{invoice}', InvoiceShow::class)->name('show');
    });
