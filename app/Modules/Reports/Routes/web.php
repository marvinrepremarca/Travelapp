<?php

declare(strict_types=1);

use App\Modules\Reports\Http\Controllers\ReportExportController;
use App\Modules\Reports\Livewire\AdvisorDashboard;
use App\Modules\Reports\Livewire\FinanceDashboard;
use App\Modules\Reports\Livewire\ManagementDashboard;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('reports')
    ->name('reports.')
    ->group(function (): void {
        // Con carga diferida el componente monta después: el permiso se exige también en la ruta.
        Route::get('/management', ManagementDashboard::class)->middleware('can:' . Permission::MarginsView->value)->name('management');
        Route::get('/advisor', AdvisorDashboard::class)->name('advisor');
        Route::get('/finance', FinanceDashboard::class)->middleware(['can:' . Permission::FinanceAccess->value, Capabilities::middleware(Capability::Accounting)])->name('finance');
        Route::get('/export/{report}', ReportExportController::class)->name('export');
    });
