<?php

declare(strict_types=1);

use App\Modules\Operations\Http\Controllers\ManifestPdfController;
use App\Modules\Operations\Livewire\DeparturesBoard;
use App\Modules\Operations\Livewire\DepartureShow;
use App\Modules\Operations\Livewire\GuideForm;
use App\Modules\Operations\Livewire\ResourcesIndex;
use App\Modules\Operations\Livewire\VehicleForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('operations')
    ->name('operations.')
    ->group(function (): void {
        Route::get('/departures', DeparturesBoard::class)->name('departures');
        Route::get('/departures/{departure}', DepartureShow::class)->name('departures.show');
        Route::get('/departures/{departure}/manifest.pdf', ManifestPdfController::class)->name('manifest');
        Route::get('/resources', ResourcesIndex::class)->name('resources');
        Route::get('/guides/create', GuideForm::class)->name('guides.create');
        Route::get('/guides/{guide}/edit', GuideForm::class)->name('guides.edit');
        Route::get('/vehicles/create', VehicleForm::class)->name('vehicles.create');
        Route::get('/vehicles/{vehicle}/edit', VehicleForm::class)->name('vehicles.edit');
    });
