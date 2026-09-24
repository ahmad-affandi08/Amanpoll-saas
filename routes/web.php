<?php

use App\Core\Host\PetaHost;
use App\Domain\Pelaporan\Http\Controllers\DasborController;
use App\Http\Controllers\Auth\DaftarTrialController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LupaKataSandiController;
use App\Http\Controllers\Auth\ResetKataSandiController;
use Illuminate\Support\Facades\Route;

// Seluruh rute sistem hidup di host dashboard (PRD 5.4).
Route::domain(app(PetaHost::class)->dashboard())->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])
            ->middleware('throttle:masuk')
            ->name('login.store');

        // Layar Pilih organisasi bila email yang sama cocok di beberapa organisasi (PRD 8.1).
        Route::get('/login/organisasi', [LoginController::class, 'pilihOrganisasi'])->name('login.organisasi');
        Route::post('/login/organisasi', [LoginController::class, 'masukKeOrganisasi'])
            ->middleware('throttle:masuk')
            ->name('login.organisasi.store');
        Route::delete('/login/organisasi', [LoginController::class, 'batalPilihOrganisasi'])
            ->name('login.organisasi.batal');

        // Formulir trial ada di host dashboard, bukan host publik (MARKETING.md 34.1).
        Route::get('/daftar', [DaftarTrialController::class, 'create'])->name('daftar');
        Route::post('/daftar', [DaftarTrialController::class, 'store'])
            ->middleware('throttle:daftar')
            ->name('daftar.store');

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
