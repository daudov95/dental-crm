<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Modules\Identity\Livewire\LoginForm;
use Modules\Identity\Livewire\StaffIndex;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', LoginForm::class)->name('login');
    });

    Route::middleware('auth')->group(function (): void {
        Route::get('/', fn () => view('identity::home'))->name('home');

        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        })->name('logout');

        Route::get('/staff', StaffIndex::class)->name('identity.staff.index');
    });
});
