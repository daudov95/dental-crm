<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Patients\Livewire\PatientIndex;
use Modules\Patients\Livewire\PatientShow;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/patients', PatientIndex::class)->name('patients.index');
    Route::get('/patients/{patient}', PatientShow::class)
        ->withTrashed()
        ->name('patients.show');
});
