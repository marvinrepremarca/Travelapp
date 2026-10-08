<?php

declare(strict_types=1);

use App\Modules\Shared\Exceptions\BusinessRuleException;

foreach (MODULES as $module) {
    $others = array_values(array_filter(MODULES, static fn(string $m): bool => $m !== $module));

    foreach ($others as $other) {
        foreach (MODULE_INTERNALS as $internal) {
            arch("{$module} does not use {$other}\\{$internal}")
                ->expect("App\\Modules\\{$module}")
                ->not->toUse("App\\Modules\\{$other}\\{$internal}");
        }
    }
}

arch('shared kernel does not depend on business modules')
    ->expect('App\Modules\Shared')
    ->not->toUse(array_map(
        static fn(string $m): string => "App\\Modules\\{$m}",
        array_values(array_filter(MODULES, static fn(string $m): bool => $m !== 'Shared')),
    ));

arch('modules use strict types')
    ->expect('App\Modules')
    ->toUseStrictTypes();

arch('actions are final and expose execute')
    ->expect(moduleLayer('Actions'))
    ->toBeFinal()
    ->toHaveMethod('execute');

arch('business exceptions extend the shared base')
    ->expect(moduleLayer('Exceptions'))
    ->toExtend(BusinessRuleException::class)
    ->ignoring('App\Modules\Shared\Exceptions');

arch('enums live in Enums folders')
    ->expect(moduleLayer('Enums'))
    ->toBeEnums();

arch('no float conversion for money')
    ->expect('App\Modules')
    ->not->toUse(['floatval', 'round']);

arch('no debugging helpers')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('env is only read from config')
    ->expect('env')
    ->toOnlyBeUsedIn('config');

arch('models use immutable dates')
    ->expect('App\Modules')
    ->not->toUse(['DateTime', \Carbon\Carbon::class, \Illuminate\Support\Carbon::class]);

arch()->preset()->security()->ignoring('assert');

arch('core modules never depend on switchable capabilities (ADR-0007)')
    ->expect(array_map(static fn(string $m): string => "App\\Modules\\{$m}", CORE_MODULES))
    ->not->toUse(array_map(
        static fn(string $m): string => "App\\Modules\\{$m}",
        array_values(array_diff(MODULES, CORE_MODULES)),
    ));
