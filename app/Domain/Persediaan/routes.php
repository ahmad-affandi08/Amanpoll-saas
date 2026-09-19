<?php

declare(strict_types=1);

use App\Domain\Persediaan\Http\Controllers\GudangController;
use App\Domain\Persediaan\Http\Controllers\KategoriSukuCadangController;
use App\Domain\Persediaan\Http\Controllers\KompatibilitasSukuCadangController;
use App\Domain\Persediaan\Http\Controllers\MutasiStokController;
use App\Domain\Persediaan\Http\Controllers\ReservasiSukuCadangController;
use App\Domain\Persediaan\Http\Controllers\StokSukuCadangController;
use App\Domain\Persediaan\Http\Controllers\SukuCadangController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])->group(function (): void {
    Route::prefix('gudang')->name('gudang.')->group(function (): void {
        Route::get('/', [GudangController::class, 'index'])->name('index');
        Route::post('/', [GudangController::class, 'store'])->name('store');
        Route::put('/{gudang}', [GudangController::class, 'update'])->name('update');
        Route::delete('/{gudang}', [GudangController::class, 'destroy'])->name('destroy');
        Route::post('/{gudang}/lokasi', [GudangController::class, 'storeLokasi'])->name('lokasi.store');
    });
    Route::put('/lokasi-gudang/{lokasiGudang}', [GudangController::class, 'updateLokasi'])->name('lokasi-gudang.update');
    Route::delete('/lokasi-gudang/{lokasiGudang}', [GudangController::class, 'destroyLokasi'])->name('lokasi-gudang.destroy');

    Route::prefix('kategori-suku-cadang')->name('kategori-suku-cadang.')->group(function (): void {
        Route::get('/', [KategoriSukuCadangController::class, 'index'])->name('index');
        Route::post('/', [KategoriSukuCadangController::class, 'store'])->name('store');
        Route::put('/{kategoriSukuCadang}', [KategoriSukuCadangController::class, 'update'])->name('update');
        Route::delete('/{kategoriSukuCadang}', [KategoriSukuCadangController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('suku-cadang')->name('suku-cadang.')->group(function (): void {
        Route::get('/', [SukuCadangController::class, 'index'])->name('index');
        Route::post('/', [SukuCadangController::class, 'store'])->name('store');
        Route::get('/{sukuCadang}', [SukuCadangController::class, 'show'])->name('show');
        Route::put('/{sukuCadang}', [SukuCadangController::class, 'update'])->name('update');
        Route::delete('/{sukuCadang}', [SukuCadangController::class, 'destroy'])->name('destroy');
        Route::post('/{sukuCadang}/kelompok', [SukuCadangController::class, 'storeKelompok'])->name('kelompok.store');
    });
    Route::put('/kelompok-suku-cadang/{kelompokSukuCadang}', [SukuCadangController::class, 'updateKelompok'])->name('kelompok-suku-cadang.update');
    Route::delete('/kelompok-suku-cadang/{kelompokSukuCadang}', [SukuCadangController::class, 'destroyKelompok'])->name('kelompok-suku-cadang.destroy');

    Route::prefix('kompatibilitas-suku-cadang')->name('kompatibilitas-suku-cadang.')->group(function (): void {
        Route::post('/', [KompatibilitasSukuCadangController::class, 'store'])->name('store');
        Route::delete('/{kompatibilitasSukuCadang}', [KompatibilitasSukuCadangController::class, 'destroy'])->name('destroy');
    });
    Route::get('/aset/{aset}/suku-cadang-kompatibel', [KompatibilitasSukuCadangController::class, 'untukAset'])->name('aset.suku-cadang-kompatibel');

    Route::get('/stok-suku-cadang', [StokSukuCadangController::class, 'index'])->name('stok-suku-cadang.index');

    Route::prefix('mutasi-stok')->name('mutasi-stok.')->group(function (): void {
        Route::get('/', [MutasiStokController::class, 'index'])->name('index');
        Route::post('/', [MutasiStokController::class, 'store'])->name('store');
        Route::get('/{mutasiStok}', [MutasiStokController::class, 'show'])->name('show');
        Route::post('/{mutasiStok}/detail', [MutasiStokController::class, 'storeDetail'])->name('detail.store');
        Route::post('/{mutasiStok}/posting', [MutasiStokController::class, 'posting'])->name('posting');
        Route::post('/{mutasiStok}/batalkan', [MutasiStokController::class, 'batalkan'])->name('batalkan');
    });
    Route::delete('/detail-mutasi-stok/{detailMutasiStok}', [MutasiStokController::class, 'destroyDetail'])->name('detail-mutasi-stok.destroy');

    Route::prefix('reservasi-suku-cadang')->name('reservasi-suku-cadang.')->group(function (): void {
        Route::get('/', [ReservasiSukuCadangController::class, 'index'])->name('index');
        Route::post('/', [ReservasiSukuCadangController::class, 'store'])->name('store');
        Route::post('/{reservasiSukuCadang}/lepaskan', [ReservasiSukuCadangController::class, 'lepaskan'])->name('lepaskan');
        Route::post('/{reservasiSukuCadang}/konsumsi', [ReservasiSukuCadangController::class, 'konsumsi'])->name('konsumsi');
    });
});
