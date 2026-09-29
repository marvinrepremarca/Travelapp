<?php

declare(strict_types=1);

namespace App\Modules\Organization\Providers;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Livewire\AgencyProfileForm;
use App\Modules\Organization\Livewire\BranchesIndex;
use App\Modules\Organization\Livewire\BranchForm;
use App\Modules\Organization\Models\Branch;
use App\Modules\Organization\Policies\BranchPolicy;
use App\Modules\Organization\Services\AgencyHolidayCalendar;
use App\Modules\Organization\Services\DatabaseAppSettings;
use App\Modules\Organization\View\BrandingComposer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'organization');

        Livewire::component('organization.agency-profile-form', AgencyProfileForm::class);
        Livewire::component('organization.branches-index', BranchesIndex::class);
        Livewire::component('organization.branch-form', BranchForm::class);

        Gate::policy(Branch::class, BranchPolicy::class);

        View::composer(['components.layouts.*', 'partials.head'], BrandingComposer::class);
    }
}
