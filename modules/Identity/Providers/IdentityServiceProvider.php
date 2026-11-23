<?php

declare(strict_types=1);

namespace Modules\Identity\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Identity\Livewire\LoginForm;
use Modules\Identity\Livewire\StaffIndex;
use modules\Identity\Models\User;
use Modules\Identity\Policies\UserPolicy;

class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'identity');

        Gate::policy(User::class, UserPolicy::class);

        Livewire::component('identity.login-form', LoginForm::class);
        Livewire::component('identity.staff-index', StaffIndex::class);
    }
}
