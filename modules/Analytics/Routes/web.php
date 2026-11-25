<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsExportController;
use Modules\Analytics\Livewire\AnalyticsDashboard;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics.index');
    Route::get('/analytics/export', AnalyticsExportController::class)->name('analytics.export');
});
