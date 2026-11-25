<?php

declare(strict_types=1);

namespace Modules\Scheduling\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Scheduling\Livewire\ScheduleIndex;
use Modules\Scheduling\Models\Appointment;
use Modules\Scheduling\Policies\AppointmentPolicy;

class SchedulingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'scheduling');

        Gate::policy(Appointment::class, AppointmentPolicy::class);

        Livewire::component('scheduling.schedule-index', ScheduleIndex::class);
    }
}
