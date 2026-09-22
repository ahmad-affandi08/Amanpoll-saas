<?php

use App\Core\Host\PetaHost;
use App\Domain\Pelaporan\Http\Controllers\DasborController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LupaKataSandiController;
use App\Http\Controllers\Auth\ResetKataSandiController;
use Illuminate\Support\Facades\Route;

/*
 * Seluruh rute sistem hidup di host dashboard (PRD 5.4). Root host ini adalah
 * dashboard organisasi, bukan landing page; pengunjung anonim diarahkan ke
 * halaman masuk oleh middleware `auth`.
 */
Route::domain(app(PetaHost::class)->dashboard())->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:masuk')
            ->name('login.store');

        Route::get('/lupa-kata-sandi', [LupaKataSandiController::class, 'create'])->name('lupa-kata-sandi');
        Route::post('/lupa-kata-sandi', [LupaKataSandiController::class, 'store'])
            ->middleware('throttle:masuk')
            ->name('lupa-kata-sandi.store');

        Route::get('/reset-kata-sandi/{penggunaId}/{token}', [ResetKataSandiController::class, 'create'])
            ->name('reset-kata-sandi');
        Route::post('/reset-kata-sandi/{penggunaId}/{token}', [ResetKataSandiController::class, 'store'])
            ->middleware('throttle:masuk')
            ->name('reset-kata-sandi.store');
    });

    Route::middleware(['auth', 'organisasi'])->group(function (): void {
        Route::get('/', DasborController::class)->name('dashboard');
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    });
});
