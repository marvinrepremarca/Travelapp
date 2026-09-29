<?php

declare(strict_types=1);

use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Organization\Providers\OrganizationServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OrganizationServiceProvider::class,
    IdentityServiceProvider::class,
];
