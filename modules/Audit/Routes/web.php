<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Audit\Livewire\AuditLogIndex;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/audit', AuditLogIndex::class)->name('audit.index');
});
