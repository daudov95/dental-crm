<?php

declare(strict_types=1);

namespace Modules\Analytics\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Analytics\Livewire\AnalyticsDashboard;
use Modules\Identity\Enums\UserRole;
use modules\Identity\Models\User;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'analytics');

        Gate::define('analytics.view', static fn (User $user): bool => $user->role === UserRole::Director);

        Livewire::component('analytics.analytics-dashboard', AnalyticsDashboard::class);
    }
}
