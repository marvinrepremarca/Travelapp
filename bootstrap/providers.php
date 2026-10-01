<?php

declare(strict_types=1);

use App\Modules\Audit\Providers\AuditServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Crm\Providers\CrmServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Integrations\Providers\IntegrationsServiceProvider;
use App\Modules\Organization\Providers\OrganizationServiceProvider;
use App\Modules\Pricing\Providers\PricingServiceProvider;
use App\Modules\Quotes\Providers\QuotesServiceProvider;
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
    PricingServiceProvider::class,
    CatalogServiceProvider::class,
    QuotesServiceProvider::class,
    IntegrationsServiceProvider::class,
];
