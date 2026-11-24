<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Billing\Livewire\BillingIndex;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/billing', BillingIndex::class)->name('billing.index');
});
