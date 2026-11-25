<?php

declare(strict_types=1);

namespace Modules\Catalog\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Catalog\Livewire\ServiceIndex;
use Modules\Catalog\Models\Service;
use Modules\Catalog\Policies\ServicePolicy;

class CatalogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'catalog');

        Gate::policy(Service::class, ServicePolicy::class);

        Livewire::component('catalog.service-index', ServiceIndex::class);
    }
}
