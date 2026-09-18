<?php

declare(strict_types=1);

use App\Domain\Platform\Http\Controllers\IzinController;
use App\Domain\Platform\Http\Controllers\PenggunaController;
use App\Domain\Platform\Http\Controllers\PenggunaPeranController;
use App\Domain\Platform\Http\Controllers\PeranController;
use App\Domain\Platform\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/izin', [IzinController::class, 'index'])->name('izin.index');

        Route::get('/peran', [PeranController::class, 'index'])->name('peran.index');
        Route::post('/peran', [PeranController::class, 'store'])->name('peran.store');
        Route::put('/peran/{peran}', [PeranController::class, 'update'])->name('peran.update');
        Route::delete('/peran/{peran}', [PeranController::class, 'destroy'])->name('peran.destroy');
        Route::put('/peran/{peran}/izin', [PeranController::class, 'sinkronkanIzin'])->name('peran.izin');

        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::put('/pengguna/{pengguna}/status', [PenggunaController::class, 'ubahStatus'])->name('pengguna.status');
        Route::post('/pengguna/{pengguna}/peran', [PenggunaPeranController::class, 'store'])->name('pengguna.peran.store');
        Route::delete('/pengguna-peran/{penggunaPeran}', [PenggunaPeranController::class, 'destroy'])->name('pengguna.peran.destroy');

        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
        Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
        Route::put('/profil/kata-sandi', [ProfilController::class, 'gantiKataSandi'])->name('profil.kata-sandi');
    });
