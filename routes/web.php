<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LupaKataSandiController;
use App\Http\Controllers\Auth\ResetKataSandiController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/lupa-kata-sandi', [LupaKataSandiController::class, 'create'])->name('lupa-kata-sandi');
    Route::post('/lupa-kata-sandi', [LupaKataSandiController::class, 'store'])->name('lupa-kata-sandi.store');

    Route::get('/reset-kata-sandi/{penggunaId}/{token}', [ResetKataSandiController::class, 'create'])->name('reset-kata-sandi');
    Route::post('/reset-kata-sandi/{penggunaId}/{token}', [ResetKataSandiController::class, 'store'])->name('reset-kata-sandi.store');
});

Route::middleware(['auth', 'organisasi'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
