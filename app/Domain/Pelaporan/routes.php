<?php

declare(strict_types=1);

use App\Domain\Pelaporan\Http\Controllers\DasborTersimpanController;
use App\Domain\Pelaporan\Http\Controllers\EksporLaporanController;
use App\Domain\Pelaporan\Http\Controllers\LaporanTersimpanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('pelaporan')
    ->name('pelaporan.')
    ->group(function (): void {
        // Laporan tersimpan (21.03).
        Route::get('/laporan', [LaporanTersimpanController::class, 'index'])->name('laporan.index');
        Route::post('/laporan', [LaporanTersimpanController::class, 'store'])->name('laporan.store');
        Route::put('/laporan/{laporanTersimpan}', [LaporanTersimpanController::class, 'update'])->name('laporan.update');
        Route::delete('/laporan/{laporanTersimpan}', [LaporanTersimpanController::class, 'destroy'])->name('laporan.destroy');

        // Ekspor (21.05). Pembuatan berkas berjalan di antrean; unduhan
        // diotorisasi ulang per berkas.
        Route::post('/ekspor', [EksporLaporanController::class, 'store'])->name('ekspor.store');
        Route::get('/ekspor/{berkas}', [EksporLaporanController::class, 'unduh'])->name('ekspor.unduh');

        // Dasbor kustom (21.04).
        Route::get('/dasbor', [DasborTersimpanController::class, 'index'])->name('dasbor.index');
        Route::post('/dasbor', [DasborTersimpanController::class, 'store'])->name('dasbor.store');
        Route::put('/dasbor/{dasborTersimpan}', [DasborTersimpanController::class, 'update'])->name('dasbor.update');
        Route::delete('/dasbor/{dasborTersimpan}', [DasborTersimpanController::class, 'destroy'])->name('dasbor.destroy');
    });
