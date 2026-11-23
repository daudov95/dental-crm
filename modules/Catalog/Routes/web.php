<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Livewire\ServiceIndex;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/services', ServiceIndex::class)->name('catalog.services.index');
});
