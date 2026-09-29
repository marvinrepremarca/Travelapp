<?php

declare(strict_types=1);

namespace App\Modules\Organization\Providers;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Services\AgencyHolidayCalendar;
use App\Modules\Organization\Services\DatabaseAppSettings;
use Illuminate\Support\ServiceProvider;

final class OrganizationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        AppSettings::class => DatabaseAppSettings::class,
        HolidayCalendar::class => AgencyHolidayCalendar::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
