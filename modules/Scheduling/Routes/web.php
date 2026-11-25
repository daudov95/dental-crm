<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Scheduling\Livewire\ScheduleIndex;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/schedule', ScheduleIndex::class)->name('scheduling.index');
});
