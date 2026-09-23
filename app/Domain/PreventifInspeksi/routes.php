<?php

declare(strict_types=1);

use App\Domain\PreventifInspeksi\Http\Controllers\ButirTemplatDaftarPeriksaController;
use App\Domain\PreventifInspeksi\Http\Controllers\InspeksiController;
use App\Domain\PreventifInspeksi\Http\Controllers\PelaksanaanDaftarPeriksaController;
use App\Domain\PreventifInspeksi\Http\Controllers\RencanaPemeliharaanController;
use App\Domain\PreventifInspeksi\Http\Controllers\TemplatDaftarPeriksaController;
use App\Domain\PreventifInspeksi\Http\Controllers\TemplatInspeksiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('preventif-inspeksi')
    ->name('preventifInspeksi.')
    ->group(function (): void {
        // Templat Daftar Periksa
        Route::get('/templat-daftar-periksa', [TemplatDaftarPeriksaController::class, 'index'])->name('templat-daftar-periksa.index');
        Route::post('/templat-daftar-periksa', [TemplatDaftarPeriksaController::class, 'store'])->name('templat-daftar-periksa.store');
        Route::get('/templat-daftar-periksa/{templatDaftarPeriksa}', [TemplatDaftarPeriksaController::class, 'show'])->name('templat-daftar-periksa.show');
        Route::put('/templat-daftar-periksa/{templatDaftarPeriksa}', [TemplatDaftarPeriksaController::class, 'update'])->name('templat-daftar-periksa.update');
        Route::post('/templat-daftar-periksa/{templatDaftarPeriksa}/versi-baru', [TemplatDaftarPeriksaController::class, 'buatVersiBaru'])->name('templat-daftar-periksa.versi-baru');
        Route::delete('/templat-daftar-periksa/{templatDaftarPeriksa}', [TemplatDaftarPeriksaController::class, 'destroy'])->name('templat-daftar-periksa.destroy');

        // Butir Templat Daftar Periksa
        Route::post('/templat-daftar-periksa/{templatDaftarPeriksa}/butir', [ButirTemplatDaftarPeriksaController::class, 'store'])->name('templat-daftar-periksa.butir.store');
        Route::put('/templat-daftar-periksa/{templatDaftarPeriksa}/butir/{butir}', [ButirTemplatDaftarPeriksaController::class, 'update'])->name('templat-daftar-periksa.butir.update');
        Route::delete('/templat-daftar-periksa/{templatDaftarPeriksa}/butir/{butir}', [ButirTemplatDaftarPeriksaController::class, 'destroy'])->name('templat-daftar-periksa.butir.destroy');
        Route::post('/templat-daftar-periksa/{templatDaftarPeriksa}/butir/urutkan', [ButirTemplatDaftarPeriksaController::class, 'urutkan'])->name('templat-daftar-periksa.butir.urutkan');

        // Pelaksanaan Daftar Periksa
        Route::post('/pelaksanaan-daftar-periksa', [PelaksanaanDaftarPeriksaController::class, 'store'])->name('pelaksanaan-daftar-periksa.store');
        Route::get('/pelaksanaan-daftar-periksa/{pelaksanaanDaftarPeriksa}', [PelaksanaanDaftarPeriksaController::class, 'show'])->name('pelaksanaan-daftar-periksa.show');
        Route::put('/pelaksanaan-daftar-periksa/{pelaksanaanDaftarPeriksa}/jawaban', [PelaksanaanDaftarPeriksaController::class, 'simpanJawaban'])->name('pelaksanaan-daftar-periksa.jawaban');
        Route::post('/pelaksanaan-daftar-periksa/{pelaksanaanDaftarPeriksa}/finalisasi', [PelaksanaanDaftarPeriksaController::class, 'finalisasi'])->name('pelaksanaan-daftar-periksa.finalisasi');

        // Rencana Pemeliharaan (Preventif)
        Route::get('/rencana-pemeliharaan', [RencanaPemeliharaanController::class, 'index'])->name('rencana-pemeliharaan.index');
        Route::get('/rencana-pemeliharaan/ekspor', [RencanaPemeliharaanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('rencana-pemeliharaan.ekspor');
        Route::post('/rencana-pemeliharaan', [RencanaPemeliharaanController::class, 'store'])->name('rencana-pemeliharaan.store');
        Route::get('/rencana-pemeliharaan/{rencanaPemeliharaan}', [RencanaPemeliharaanController::class, 'show'])->name('rencana-pemeliharaan.show');
        Route::put('/rencana-pemeliharaan/{rencanaPemeliharaan}', [RencanaPemeliharaanController::class, 'update'])->name('rencana-pemeliharaan.update');
        Route::post('/rencana-pemeliharaan/{rencanaPemeliharaan}/aset', [RencanaPemeliharaanController::class, 'tetapkanAset'])->name('rencana-pemeliharaan.aset.store');
        Route::delete('/rencana-pemeliharaan/{rencanaPemeliharaan}/aset/{asetId}', [RencanaPemeliharaanController::class, 'lepasAset'])->name('rencana-pemeliharaan.aset.destroy');
        Route::post('/rencana-pemeliharaan/jalankan-scheduler', [RencanaPemeliharaanController::class, 'jalankanScheduler'])->name('rencana-pemeliharaan.scheduler');

        // Templat Inspeksi
        Route::get('/templat-inspeksi', [TemplatInspeksiController::class, 'index'])->name('templat-inspeksi.index');
        Route::post('/templat-inspeksi', [TemplatInspeksiController::class, 'store'])->name('templat-inspeksi.store');
        Route::put('/templat-inspeksi/{templatInspeksi}', [TemplatInspeksiController::class, 'update'])->name('templat-inspeksi.update');

        // Inspeksi
        Route::get('/inspeksi', [InspeksiController::class, 'index'])->name('inspeksi.index');
        Route::get('/inspeksi/ekspor', [InspeksiController::class, 'ekspor'])->middleware('throttle:ekspor')->name('inspeksi.ekspor');
        Route::post('/inspeksi', [InspeksiController::class, 'store'])->name('inspeksi.store');
        Route::get('/inspeksi/{inspeksi}', [InspeksiController::class, 'show'])->name('inspeksi.show');
        Route::post('/inspeksi/{inspeksi}/laksanakan', [InspeksiController::class, 'laksanakan'])->name('inspeksi.laksanakan');
        Route::post('/inspeksi/{inspeksi}/buat-perintah-kerja', [InspeksiController::class, 'buatPerintahKerja'])->name('inspeksi.buat-perintah-kerja');
    });
