<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Deals\Livewire\DealShow;
use Modules\Deals\Livewire\DealsIndex;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/deals', DealsIndex::class)->name('deals.index');
    Route::get('/deals/{deal}', DealShow::class)->name('deals.show');
});
