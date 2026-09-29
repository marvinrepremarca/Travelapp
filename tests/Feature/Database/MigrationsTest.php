<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('rolls back and re-applies every migration', function (): void {
    expect(Artisan::call('migrate:rollback', ['--force' => true]))->toBe(0)
        ->and(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('branches'))->toBeFalse();

    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Schema::hasTable('users'))->toBeTrue()
        ->and(Schema::hasTable('branches'))->toBeTrue();
});
