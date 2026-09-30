<?php

declare(strict_types=1);

use App\Modules\Identity\Livewire\SecuritySettings;
use App\Modules\Identity\Livewire\UserForm;
use App\Modules\Identity\Livewire\UsersIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('identity')
    ->name('identity.')
    ->group(function (): void {
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');

        Route::get('/security', SecuritySettings::class)->middleware('password.confirm')->name('security');
    });
