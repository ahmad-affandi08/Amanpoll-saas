<?php

declare(strict_types=1);

use App\Domain\SiklusAset\Http\Controllers\PengajuanPenghapusanAsetController;
use App\Domain\SiklusAset\Http\Controllers\PermintaanMutasiAsetController;
use App\Domain\SiklusAset\Http\Controllers\SerahTerimaAsetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])->group(function (): void {
    Route::prefix('mutasi-aset')->name('mutasiAset.')->group(function (): void {
        Route::get('/', [PermintaanMutasiAsetController::class, 'index'])->name('index');
        Route::post('/', [PermintaanMutasiAsetController::class, 'store'])->name('store');
        Route::get('/{permintaanMutasiAset}', [PermintaanMutasiAsetController::class, 'show'])->name('show');
        Route::post('/{permintaanMutasiAset}/detail', [PermintaanMutasiAsetController::class, 'storeDetail'])->name('detail.store');
        Route::delete('/detail/{detailMutasiAset}', [PermintaanMutasiAsetController::class, 'destroyDetail'])->name('detail.destroy');
        Route::post('/detail/{detailMutasiAset}/putuskan', [PermintaanMutasiAsetController::class, 'putuskanDetail'])->name('detail.putuskan');
        Route::post('/{permintaanMutasiAset}/pindai', [PermintaanMutasiAsetController::class, 'pindai'])->name('pindai');
        Route::post('/{permintaanMutasiAset}/submit', [PermintaanMutasiAsetController::class, 'submit'])->name('submit');
        Route::post('/{permintaanMutasiAset}/batalkan', [PermintaanMutasiAsetController::class, 'batalkan'])->name('batalkan');
        Route::post('/{permintaanMutasiAset}/eksekusi', [PermintaanMutasiAsetController::class, 'eksekusi'])->name('eksekusi');
    });

    Route::prefix('serah-terima-aset')->name('serahTerimaAset.')->group(function (): void {
        Route::get('/', [SerahTerimaAsetController::class, 'index'])->name('index');
        Route::post('/', [SerahTerimaAsetController::class, 'store'])->name('store');
        Route::get('/{serahTerimaAset}', [SerahTerimaAsetController::class, 'show'])->name('show');
        Route::post('/{serahTerimaAset}/detail', [SerahTerimaAsetController::class, 'storeDetail'])->name('detail.store');
        Route::post('/{serahTerimaAset}/terima', [SerahTerimaAsetController::class, 'terima'])->name('terima');
    });

    Route::prefix('penghapusan-aset')->name('penghapusanAset.')->group(function (): void {
        Route::get('/', [PengajuanPenghapusanAsetController::class, 'index'])->name('index');
        Route::post('/', [PengajuanPenghapusanAsetController::class, 'store'])->name('store');
        Route::get('/{pengajuanPenghapusanAset}', [PengajuanPenghapusanAsetController::class, 'show'])->name('show');
        Route::post('/{pengajuanPenghapusanAset}/detail', [PengajuanPenghapusanAsetController::class, 'storeDetail'])->name('detail.store');
        Route::delete('/detail/{detailPenghapusanAset}', [PengajuanPenghapusanAsetController::class, 'destroyDetail'])->name('detail.destroy');
        Route::post('/{pengajuanPenghapusanAset}/submit', [PengajuanPenghapusanAsetController::class, 'submit'])->name('submit');
        Route::post('/{pengajuanPenghapusanAset}/batalkan', [PengajuanPenghapusanAsetController::class, 'batalkan'])->name('batalkan');
        Route::post('/{pengajuanPenghapusanAset}/eksekusi', [PengajuanPenghapusanAsetController::class, 'eksekusi'])->name('eksekusi');
    });
});
