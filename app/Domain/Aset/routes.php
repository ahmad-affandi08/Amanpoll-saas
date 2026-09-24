<?php

declare(strict_types=1);

use App\Domain\Aset\Http\Controllers\AsetController;
use App\Domain\Aset\Http\Controllers\AsetPindaiController;
use App\Domain\Aset\Http\Controllers\GaransiAsetController;
use App\Domain\Aset\Http\Controllers\ImporAsetController;
use App\Domain\Aset\Http\Controllers\KartuRiwayatAsetController;
use App\Domain\Aset\Http\Controllers\KategoriAsetController;
use App\Domain\Aset\Http\Controllers\KelayakanAsetController;
use App\Domain\Aset\Http\Controllers\LabelAsetController;
use App\Domain\Aset\Http\Controllers\MerekController;
use App\Domain\Aset\Http\Controllers\MeterAsetController;
use App\Domain\Aset\Http\Controllers\ModelAsetController;
use App\Domain\Aset\Http\Controllers\NilaiAsetController;
use App\Domain\Aset\Http\Controllers\PembacaanMeterController;
use App\Domain\Aset\Http\Controllers\RelasiAsetController;
use App\Domain\Aset\Http\Controllers\RiwayatAsetController;
use App\Domain\Aset\Http\Controllers\RiwayatLokasiAsetController;
use App\Domain\Aset\Http\Controllers\RiwayatPenanggungJawabAsetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->group(function (): void {
        Route::prefix('aset-master')->name('aset-master.')->group(function (): void {
            Route::post('/kategori', [KategoriAsetController::class, 'store'])->name('kategori.store');
            Route::put('/kategori/{kategoriAset}', [KategoriAsetController::class, 'update'])->name('kategori.update');
            Route::delete('/kategori/{kategoriAset}', [KategoriAsetController::class, 'destroy'])->name('kategori.destroy');
            Route::get('/kategori', [KategoriAsetController::class, 'index'])->name('kategori.index');
            Route::get('/kategori/ekspor', [KategoriAsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('kategori.ekspor');

            Route::get('/merek', [MerekController::class, 'index'])->name('merek.index');
            Route::get('/merek/ekspor', [MerekController::class, 'ekspor'])->middleware('throttle:ekspor')->name('merek.ekspor');
            Route::post('/merek', [MerekController::class, 'store'])->name('merek.store');
            Route::put('/merek/{merek}', [MerekController::class, 'update'])->name('merek.update');
            Route::delete('/merek/{merek}', [MerekController::class, 'destroy'])->name('merek.destroy');

            Route::get('/model', [ModelAsetController::class, 'index'])->name('model.index');
            Route::get('/model/ekspor', [ModelAsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('model.ekspor');
            Route::post('/model', [ModelAsetController::class, 'store'])->name('model.store');
            Route::put('/model/{modelAset}', [ModelAsetController::class, 'update'])->name('model.update');
            Route::delete('/model/{modelAset}', [ModelAsetController::class, 'destroy'])->name('model.destroy');
        });

        Route::prefix('aset')->name('aset.')->group(function (): void {
            Route::get('/', [AsetController::class, 'index'])->name('index');
            Route::post('/', [AsetController::class, 'store'])->name('store');
            Route::get('/pindai/{kode}', [AsetPindaiController::class, 'tampilkan'])->name('pindai');
            // Sebelum '/{aset}' supaya 'ekspor' tidak tertelan sebagai id aset.
            Route::get('/ekspor', [AsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('ekspor');
            // Impor (PRD 8.4). Sebelum '/{aset}' supaya 'impor' tidak tertelan sebagai id aset.
            Route::get('/impor/templat', [ImporAsetController::class, 'templat'])->middleware('throttle:ekspor')->name('impor.templat');
            Route::post('/impor/pratinjau', [ImporAsetController::class, 'pratinjau'])->name('impor.pratinjau');
            Route::post('/impor/galat', [ImporAsetController::class, 'galat'])->middleware('throttle:ekspor')->name('impor.galat');
            Route::post('/impor', [ImporAsetController::class, 'simpan'])->name('impor.simpan');
            // Sebelum '/{aset}' supaya 'unit-pengelola' tidak tertelan sebagai id aset.
            Route::put('/unit-pengelola', [AsetController::class, 'aturUnitPengelola'])->name('unit-pengelola');
            // Sebelum '/{aset}' supaya 'label' tidak tertelan sebagai id aset.
            Route::get('/label', LabelAsetController::class)->name('label');
            Route::get('/kelayakan', [KelayakanAsetController::class, 'index'])->name('kelayakan.index');
            Route::get('/kelayakan/ekspor', [KelayakanAsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('kelayakan.ekspor');
            Route::get('/{aset}', [AsetController::class, 'show'])->name('show');
            Route::put('/{aset}', [AsetController::class, 'update'])->name('update');
            Route::delete('/{aset}', [AsetController::class, 'destroy'])->name('destroy');

            Route::get('/{aset}/kelayakan', [KelayakanAsetController::class, 'satu'])->name('kelayakan.satu');
            Route::get('/{aset}/kartu-riwayat', [KartuRiwayatAsetController::class, 'cetak'])->middleware('throttle:ekspor')->name('kartu-riwayat');
            Route::get('/{aset}/riwayat-pemeliharaan', [RiwayatAsetController::class, 'pemeliharaan'])->name('riwayat-pemeliharaan.index');
            Route::get('/{aset}/riwayat-kalibrasi', [RiwayatAsetController::class, 'kalibrasi'])->name('riwayat-kalibrasi.index');

            Route::get('/{aset}/riwayat-lokasi', [RiwayatLokasiAsetController::class, 'index'])->name('riwayat-lokasi.index');
            Route::get('/{aset}/riwayat-lokasi/ekspor', [RiwayatLokasiAsetController::class, 'ekspor'])->middleware('throttle:ekspor')->name('riwayat-lokasi.ekspor');
            Route::post('/{aset}/riwayat-lokasi', [RiwayatLokasiAsetController::class, 'store'])->name('riwayat-lokasi.store');

            Route::get('/{aset}/penanggung-jawab', [RiwayatPenanggungJawabAsetController::class, 'index'])->name('penanggung-jawab.index');
            Route::post('/{aset}/penanggung-jawab', [RiwayatPenanggungJawabAsetController::class, 'store'])->name('penanggung-jawab.store');

            Route::get('/{aset}/relasi', [RelasiAsetController::class, 'index'])->name('relasi.index');
            Route::post('/{aset}/relasi', [RelasiAsetController::class, 'store'])->name('relasi.store');
            Route::delete('/relasi/{relasiAset}', [RelasiAsetController::class, 'destroy'])->name('relasi.destroy');

            Route::get('/{aset}/garansi', [GaransiAsetController::class, 'index'])->name('garansi.index');
            Route::post('/{aset}/garansi', [GaransiAsetController::class, 'store'])->name('garansi.store');
            Route::put('/garansi/{garansiAset}', [GaransiAsetController::class, 'update'])->name('garansi.update');
            Route::delete('/garansi/{garansiAset}', [GaransiAsetController::class, 'destroy'])->name('garansi.destroy');

            Route::get('/{aset}/nilai', [NilaiAsetController::class, 'index'])->name('nilai.index');
            Route::get('/{aset}/nilai/pratinjau', [NilaiAsetController::class, 'pratinjau'])->name('nilai.pratinjau');
            Route::post('/{aset}/nilai', [NilaiAsetController::class, 'store'])->name('nilai.store');

            Route::get('/{aset}/meter', [MeterAsetController::class, 'index'])->name('meter.index');
            Route::post('/{aset}/meter', [MeterAsetController::class, 'store'])->name('meter.store');
            Route::put('/meter/{meterAset}', [MeterAsetController::class, 'update'])->name('meter.update');

            Route::get('/meter/{meterAset}/pembacaan', [PembacaanMeterController::class, 'index'])->name('meter.pembacaan.index');
            Route::post('/meter/{meterAset}/pembacaan', [PembacaanMeterController::class, 'store'])->name('meter.pembacaan.store');
        });
    });
