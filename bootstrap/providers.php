<?php

declare(strict_types=1);

use App\Modules\Audit\Providers\AuditServiceProvider;
use App\Modules\Bookings\Providers\BookingsServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Crm\Providers\CrmServiceProvider;
use App\Modules\Documents\Providers\DocumentsServiceProvider;
use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Integrations\Providers\IntegrationsServiceProvider;
use App\Modules\Organization\Providers\OrganizationServiceProvider;
use App\Modules\Payments\Providers\PaymentsServiceProvider;
use App\Modules\Pricing\Providers\PricingServiceProvider;
use App\Modules\Quotes\Providers\QuotesServiceProvider;
use App\Modules\Search\Providers\SearchServiceProvider;
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
    DocumentsServiceProvider::class,
    QuotesServiceProvider::class,
    BookingsServiceProvider::class,
    SearchServiceProvider::class,
    PaymentsServiceProvider::class,
    FinanceServiceProvider::class,
    IntegrationsServiceProvider::class,
];
