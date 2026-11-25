<?php

return [
    App\Providers\AppServiceProvider::class,
    Modules\Identity\Providers\IdentityServiceProvider::class,
    Modules\Patients\Providers\PatientsServiceProvider::class,
    Modules\Catalog\Providers\CatalogServiceProvider::class,
    Modules\Deals\Providers\DealsServiceProvider::class,
    Modules\Scheduling\Providers\SchedulingServiceProvider::class,
    Modules\Billing\Providers\BillingServiceProvider::class,
    Modules\Analytics\Providers\AnalyticsServiceProvider::class,
    Modules\Audit\Providers\AuditServiceProvider::class,
];
