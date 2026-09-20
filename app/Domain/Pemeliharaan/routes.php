<?php

declare(strict_types=1);

use App\Domain\Pemeliharaan\Http\Controllers\KategoriKeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\KeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\KodeKegagalanController;
use App\Domain\Pemeliharaan\Http\Controllers\OperasionalPerintahKerjaController;
use App\Domain\Pemeliharaan\Http\Controllers\PenugasanPerintahKerjaController;
use App\Domain\Pemeliharaan\Http\Controllers\PerintahKerjaController;
use App\Domain\Pemeliharaan\Http\Controllers\TingkatLayananController;
use App\Domain\Pemeliharaan\Http\Controllers\WaktuHentiAsetController;
use App\Domain\Pemeliharaan\Http\Controllers\WaktuKerjaController;
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

        Route::get('/perintah-kerja', [PerintahKerjaController::class, 'index'])->name('perintah-kerja.index');
        Route::post('/perintah-kerja', [PerintahKerjaController::class, 'store'])->name('perintah-kerja.store');
        Route::get('/perintah-kerja/{perintahKerja}', [PerintahKerjaController::class, 'show'])->name('perintah-kerja.show');
        Route::put('/perintah-kerja/{perintahKerja}/status', [PerintahKerjaController::class, 'ubahStatus'])->name('perintah-kerja.status');
        Route::post('/perintah-kerja/{perintahKerja}/penugasan', [PenugasanPerintahKerjaController::class, 'store'])->name('perintah-kerja.penugasan.store');
        Route::post('/perintah-kerja/{perintahKerja}/penugasan/{penugasan}/respons', [PenugasanPerintahKerjaController::class, 'respons'])->name('perintah-kerja.penugasan.respons');
        Route::post('/perintah-kerja/{perintahKerja}/waktu-kerja', [WaktuKerjaController::class, 'store'])->name('perintah-kerja.waktu-kerja');
        Route::post('/perintah-kerja/{perintahKerja}/waktu-henti', [WaktuHentiAsetController::class, 'store'])->name('perintah-kerja.waktu-henti');
        Route::post('/perintah-kerja/{perintahKerja}/reservasi-suku-cadang', [OperasionalPerintahKerjaController::class, 'reservasi'])->name('perintah-kerja.reservasi');
        Route::post('/perintah-kerja/{perintahKerja}/suku-cadang', [OperasionalPerintahKerjaController::class, 'sukuCadang'])->name('perintah-kerja.suku-cadang');
        Route::post('/perintah-kerja/{perintahKerja}/biaya', [OperasionalPerintahKerjaController::class, 'biaya'])->name('perintah-kerja.biaya');
        Route::put('/perintah-kerja/{perintahKerja}/analisis-kegagalan', [OperasionalPerintahKerjaController::class, 'analisis'])->name('perintah-kerja.analisis');

        Route::get('/kode-kegagalan', [KodeKegagalanController::class, 'index'])->name('kode-kegagalan.index');
        Route::post('/kode-kegagalan', [KodeKegagalanController::class, 'store'])->name('kode-kegagalan.store');
        Route::put('/kode-kegagalan/{kodeKegagalan}', [KodeKegagalanController::class, 'update'])->name('kode-kegagalan.update');
    });
