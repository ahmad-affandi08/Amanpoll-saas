<?php

declare(strict_types=1);

use App\Domain\Pemeliharaan\Http\Controllers\KategoriKeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\KeluhanController;
use App\Domain\Pemeliharaan\Http\Controllers\KodeKegagalanController;
use App\Domain\Pemeliharaan\Http\Controllers\KonfirmasiPenerimaController;
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
        // Sebelum '/tingkat-layanan/{tingkatLayanan}' supaya 'ekspor' tidak tertelan sebagai id.
        Route::get('/tingkat-layanan/ekspor', [TingkatLayananController::class, 'ekspor'])->middleware('throttle:ekspor')->name('tingkat-layanan.ekspor');
        Route::post('/tingkat-layanan', [TingkatLayananController::class, 'store'])->name('tingkat-layanan.store');
        Route::put('/tingkat-layanan/{tingkatLayanan}', [TingkatLayananController::class, 'update'])->name('tingkat-layanan.update');
        Route::delete('/tingkat-layanan/{tingkatLayanan}', [TingkatLayananController::class, 'destroy'])->name('tingkat-layanan.destroy');

        Route::get('/kategori-keluhan', [KategoriKeluhanController::class, 'index'])->name('kategori-keluhan.index');
        Route::get('/kategori-keluhan/ekspor', [KategoriKeluhanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('kategori-keluhan.ekspor');
        Route::post('/kategori-keluhan', [KategoriKeluhanController::class, 'store'])->name('kategori-keluhan.store');
        Route::put('/kategori-keluhan/{kategoriKeluhan}', [KategoriKeluhanController::class, 'update'])->name('kategori-keluhan.update');
        Route::delete('/kategori-keluhan/{kategoriKeluhan}', [KategoriKeluhanController::class, 'destroy'])->name('kategori-keluhan.destroy');

        Route::get('/keluhan', [KeluhanController::class, 'index'])->name('keluhan.index');
        // Sebelum '/keluhan/{keluhan}' supaya 'ekspor' tidak tertelan sebagai id.
        Route::get('/keluhan/ekspor', [KeluhanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('keluhan.ekspor');
        Route::post('/keluhan', [KeluhanController::class, 'store'])->name('keluhan.store');
        Route::get('/keluhan/{keluhan}', [KeluhanController::class, 'show'])->name('keluhan.show');
        Route::put('/keluhan/{keluhan}/status', [KeluhanController::class, 'ubahStatus'])->name('keluhan.status');
        Route::put('/keluhan/{keluhan}/prioritas', [KeluhanController::class, 'ubahPrioritas'])->name('keluhan.prioritas');
        Route::put('/keluhan/{keluhan}/unit-pengelola', [KeluhanController::class, 'alihkanUnitPengelola'])->name('keluhan.unit-pengelola');

        Route::get('/perintah-kerja', [PerintahKerjaController::class, 'index'])->name('perintah-kerja.index');
        Route::get('/perintah-kerja/ekspor', [PerintahKerjaController::class, 'ekspor'])->middleware('throttle:ekspor')->name('perintah-kerja.ekspor');
        Route::post('/perintah-kerja', [PerintahKerjaController::class, 'store'])->name('perintah-kerja.store');
        Route::get('/perintah-kerja/{perintahKerja}', [PerintahKerjaController::class, 'show'])->name('perintah-kerja.show');
        Route::put('/perintah-kerja/{perintahKerja}/status', [PerintahKerjaController::class, 'ubahStatus'])->name('perintah-kerja.status');
        Route::put('/perintah-kerja/{perintahKerja}/unit-pengelola', [PerintahKerjaController::class, 'alihkanUnitPengelola'])->name('perintah-kerja.unit-pengelola');
        Route::post('/perintah-kerja/{perintahKerja}/penugasan', [PenugasanPerintahKerjaController::class, 'store'])->name('perintah-kerja.penugasan.store');
        Route::post('/perintah-kerja/{perintahKerja}/penugasan/{penugasan}/respons', [PenugasanPerintahKerjaController::class, 'respons'])->name('perintah-kerja.penugasan.respons');
        Route::post('/perintah-kerja/{perintahKerja}/waktu-kerja', [WaktuKerjaController::class, 'store'])->name('perintah-kerja.waktu-kerja');
        Route::post('/perintah-kerja/{perintahKerja}/waktu-henti', [WaktuHentiAsetController::class, 'store'])->name('perintah-kerja.waktu-henti');
        Route::post('/perintah-kerja/{perintahKerja}/reservasi-suku-cadang', [OperasionalPerintahKerjaController::class, 'reservasi'])->name('perintah-kerja.reservasi');
        Route::post('/perintah-kerja/{perintahKerja}/suku-cadang', [OperasionalPerintahKerjaController::class, 'sukuCadang'])->name('perintah-kerja.suku-cadang');
        Route::post('/perintah-kerja/{perintahKerja}/biaya', [OperasionalPerintahKerjaController::class, 'biaya'])->name('perintah-kerja.biaya');
        Route::put('/perintah-kerja/{perintahKerja}/analisis-kegagalan', [OperasionalPerintahKerjaController::class, 'analisis'])->name('perintah-kerja.analisis');
        // Gambar tanda tangan yang dicap pada konfirmasi penerima (PRD 8.22); policy `view` perintah kerjanya.
        Route::get('/perintah-kerja/{perintahKerja}/konfirmasi-penerima/{konfirmasi}/tanda-tangan', [KonfirmasiPenerimaController::class, 'tandaTangan'])->name('perintah-kerja.konfirmasi-penerima.tanda-tangan');

        Route::get('/kode-kegagalan', [KodeKegagalanController::class, 'index'])->name('kode-kegagalan.index');
        // Sebelum '/kode-kegagalan/{kodeKegagalan}' supaya 'ekspor' tidak tertelan sebagai id.
        Route::get('/kode-kegagalan/ekspor', [KodeKegagalanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('kode-kegagalan.ekspor');
        Route::post('/kode-kegagalan', [KodeKegagalanController::class, 'store'])->name('kode-kegagalan.store');
        Route::put('/kode-kegagalan/{kodeKegagalan}', [KodeKegagalanController::class, 'update'])->name('kode-kegagalan.update');
    });
