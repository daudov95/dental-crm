<?php

declare(strict_types=1);

namespace Modules\Deals\Providers;

use Illuminate\Support\ServiceProvider;

class DealsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
