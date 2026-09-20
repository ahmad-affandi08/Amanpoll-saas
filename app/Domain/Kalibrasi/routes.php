<?php

declare(strict_types=1);

use App\Domain\Kalibrasi\Http\Controllers\JenisKalibrasiController;
use App\Domain\Kalibrasi\Http\Controllers\KalibrasiDashboardController;
use App\Domain\Kalibrasi\Http\Controllers\PelaksanaanKalibrasiController;
use App\Domain\Kalibrasi\Http\Controllers\RencanaKalibrasiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kalibrasi')
    ->name('kalibrasi.')
    ->group(function (): void {
        // Dasbor Kepatuhan Kalibrasi (14.05)
        Route::get('/', [KalibrasiDashboardController::class, 'index'])->name('index');

        // Jenis Kalibrasi & Titik Ukur Standar (14.01 & 14.04)
        Route::get('/jenis', [JenisKalibrasiController::class, 'index'])->name('jenis.index');
        Route::post('/jenis', [JenisKalibrasiController::class, 'store'])->name('jenis.store');
        Route::put('/jenis/{jenisKalibrasi}', [JenisKalibrasiController::class, 'update'])->name('jenis.update');
        Route::delete('/jenis/{jenisKalibrasi}', [JenisKalibrasiController::class, 'destroy'])->name('jenis.destroy');
        Route::post('/jenis/{jenisKalibrasi}/titik-ukur', [JenisKalibrasiController::class, 'tambahTitikUkur'])->name('jenis.titik-ukur.store');
        Route::put('/titik-ukur/{titikUkurKalibrasi}', [JenisKalibrasiController::class, 'updateTitikUkur'])->name('titik-ukur.update');
        Route::delete('/titik-ukur/{titikUkurKalibrasi}', [JenisKalibrasiController::class, 'hapusTitikUkur'])->name('titik-ukur.destroy');

        // Rencana Kalibrasi (14.02 & 14.05)
        Route::get('/rencana', [RencanaKalibrasiController::class, 'index'])->name('rencana.index');
        Route::post('/rencana', [RencanaKalibrasiController::class, 'store'])->name('rencana.store');
        Route::get('/rencana/{rencanaKalibrasi}', [RencanaKalibrasiController::class, 'show'])->name('rencana.show');
        Route::put('/rencana/{rencanaKalibrasi}', [RencanaKalibrasiController::class, 'update'])->name('rencana.update');
        Route::delete('/rencana/{rencanaKalibrasi}', [RencanaKalibrasiController::class, 'destroy'])->name('rencana.destroy');
        Route::post('/rencana/jalankan-pengingat', [RencanaKalibrasiController::class, 'jalankanPengingat'])->name('rencana.pengingat');

        // Pelaksanaan Kalibrasi (14.03 & 14.04)
        Route::get('/pelaksanaan', [PelaksanaanKalibrasiController::class, 'index'])->name('pelaksanaan.index');
        Route::post('/pelaksanaan', [PelaksanaanKalibrasiController::class, 'store'])->name('pelaksanaan.store');
        Route::get('/pelaksanaan/{pelaksanaanKalibrasi}', [PelaksanaanKalibrasiController::class, 'show'])->name('pelaksanaan.show');
        Route::put('/pelaksanaan/{pelaksanaanKalibrasi}/hasil-titik-ukur', [PelaksanaanKalibrasiController::class, 'simpanHasilTitikUkur'])->name('pelaksanaan.hasil-titik-ukur');
        Route::post('/pelaksanaan/{pelaksanaanKalibrasi}/finalisasi', [PelaksanaanKalibrasiController::class, 'finalisasi'])->name('pelaksanaan.finalisasi');
        Route::delete('/pelaksanaan/{pelaksanaanKalibrasi}', [PelaksanaanKalibrasiController::class, 'destroy'])->name('pelaksanaan.destroy');
    });
