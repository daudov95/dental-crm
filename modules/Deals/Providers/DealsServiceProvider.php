<?php

declare(strict_types=1);

namespace Modules\Deals\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Deals\Livewire\DealShow;
use Modules\Deals\Livewire\DealsIndex;
use Modules\Deals\Models\Deal;
use Modules\Deals\Policies\DealPolicy;

class DealsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'deals');

        Gate::policy(Deal::class, DealPolicy::class);

        Livewire::component('deals.deals-index', DealsIndex::class);
        Livewire::component('deals.deal-show', DealShow::class);
    }
}
