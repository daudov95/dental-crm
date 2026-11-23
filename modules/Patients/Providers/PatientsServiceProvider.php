<?php

declare(strict_types=1);

namespace Modules\Patients\Providers;

use Illuminate\Support\ServiceProvider;

class PatientsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
