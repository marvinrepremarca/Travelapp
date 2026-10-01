<?php

declare(strict_types=1);

use App\Modules\Audit\Providers\AuditServiceProvider;
use App\Modules\Crm\Providers\CrmServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Organization\Providers\OrganizationServiceProvider;
use App\Modules\Suppliers\Providers\SuppliersServiceProvider;
use App\Modules\Workflow\Providers\WorkflowServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    OrganizationServiceProvider::class,
    IdentityServiceProvider::class,
    AuditServiceProvider::class,
    WorkflowServiceProvider::class,
    CrmServiceProvider::class,
    SuppliersServiceProvider::class,
];
