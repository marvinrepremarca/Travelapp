<?php

declare(strict_types=1);

use App\Modules\Compliance\Livewire\ComplianceOverview;
use App\Modules\Compliance\Livewire\DataRequestForm;
use App\Modules\Compliance\Livewire\DataRequestShow;
use App\Modules\Compliance\Livewire\DataRequestsIndex;
use App\Modules\Compliance\Livewire\DocumentForm;
use App\Modules\Compliance\Livewire\DocumentsIndex;
use App\Modules\Compliance\Livewire\ObligationForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('compliance')
    ->name('compliance.')
    ->group(function (): void {
        Route::get('/', ComplianceOverview::class)->name('index');
        Route::get('/documents', DocumentsIndex::class)->name('documents');
        Route::get('/documents/create', DocumentForm::class)->name('documents.create');
        Route::get('/documents/{document}/edit', DocumentForm::class)->name('documents.edit');
        Route::get('/obligations/create', ObligationForm::class)->name('obligations.create');
        Route::get('/obligations/{obligation}/edit', ObligationForm::class)->name('obligations.edit');
        Route::get('/requests', DataRequestsIndex::class)->name('requests');
        Route::get('/requests/create', DataRequestForm::class)->name('requests.create');
        Route::get('/requests/{request}', DataRequestShow::class)->name('requests.show');
    });
