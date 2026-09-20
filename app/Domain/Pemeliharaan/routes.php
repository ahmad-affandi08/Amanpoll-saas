<?php

declare(strict_types=1);

use App\Domain\Pemeliharaan\Http\Controllers\KategoriKeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\KeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\TingkatLayananController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('pemeliharaan')
    ->name('pemeliharaan.')
    ->group(function (): void {
        Route::get('/tingkat-layanan', [TingkatLayananController::class, 'index'])->name('tingkat-layanan.index');
        Route::post('/tingkat-layanan', [TingkatLayananController::class, 'store'])->name('tingkat-layanan.store');
        Route::put('/tingkat-layanan/{tingkatLayanan}', [TingkatLayananController::class, 'update'])->name('tingkat-layanan.update');
        Route::delete('/tingkat-layanan/{tingkatLayanan}', [TingkatLayananController::class, 'destroy'])->name('tingkat-layanan.destroy');

        Route::get('/kategori-keluhan', [KategoriKeluhanController::class, 'index'])->name('kategori-keluhan.index');
        Route::post('/kategori-keluhan', [KategoriKeluhanController::class, 'store'])->name('kategori-keluhan.store');
        Route::put('/kategori-keluhan/{kategoriKeluhan}', [KategoriKeluhanController::class, 'update'])->name('kategori-keluhan.update');
        Route::delete('/kategori-keluhan/{kategoriKeluhan}', [KategoriKeluhanController::class, 'destroy'])->name('kategori-keluhan.destroy');

        Route::get('/keluhan', [KeluhanController::class, 'index'])->name('keluhan.index');
        Route::post('/keluhan', [KeluhanController::class, 'store'])->name('keluhan.store');
        Route::get('/keluhan/{keluhan}', [KeluhanController::class, 'show'])->name('keluhan.show');
        Route::put('/keluhan/{keluhan}/status', [KeluhanController::class, 'ubahStatus'])->name('keluhan.status');
        Route::put('/keluhan/{keluhan}/prioritas', [KeluhanController::class, 'ubahPrioritas'])->name('keluhan.prioritas');
    });
