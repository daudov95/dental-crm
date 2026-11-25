<?php

declare(strict_types=1);

namespace Modules\Patients\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Patients\Livewire\PatientIndex;
use Modules\Patients\Livewire\PatientShow;
use Modules\Patients\Models\Patient;
use Modules\Patients\Policies\PatientPolicy;

class PatientsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'patients');

        Gate::policy(Patient::class, PatientPolicy::class);

        Livewire::component('patients.patient-index', PatientIndex::class);
        Livewire::component('patients.patient-show', PatientShow::class);
    }
}
