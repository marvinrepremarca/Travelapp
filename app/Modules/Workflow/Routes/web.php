<?php

declare(strict_types=1);

use App\Modules\Workflow\Livewire\ApprovalsInbox;
use App\Modules\Workflow\Livewire\TasksBoard;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('workflow')
    ->name('workflow.')
    ->group(function (): void {
        Route::get('/tasks', TasksBoard::class)->name('tasks');
        Route::get('/approvals', ApprovalsInbox::class)->name('approvals');
    });
