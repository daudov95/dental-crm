<?php

declare(strict_types=1);

namespace Modules\Billing\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Billing\Jobs\CheckOverdueInstallmentsJob;
use Modules\Billing\Jobs\GenerateTomorrowRemindersJob;
use Modules\Billing\Livewire\BillingIndex;
use Modules\Billing\Models\Payment;
use Modules\Billing\Policies\PaymentPolicy;

class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'billing');

        Gate::policy(Payment::class, PaymentPolicy::class);

        Livewire::component('billing.billing-index', BillingIndex::class);

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->job(new CheckOverdueInstallmentsJob())->dailyAt('08:00');
            $schedule->job(new GenerateTomorrowRemindersJob())->dailyAt('18:00');
        });
    }
}
