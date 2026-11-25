<?php

declare(strict_types=1);

namespace Modules\Audit\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Audit\Listeners\RecordDomainAuditSubscriber;
use Modules\Audit\Livewire\AuditLogIndex;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Policies\AuditLogPolicy;

class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Views', 'audit');

        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        Livewire::component('audit.audit-log-index', AuditLogIndex::class);

        Event::subscribe(RecordDomainAuditSubscriber::class);
    }
}
