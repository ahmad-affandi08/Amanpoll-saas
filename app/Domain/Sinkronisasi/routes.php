<?php

declare(strict_types=1);

use App\Domain\IntegrasiAudit\Http\Controllers\PanggilanBalikWebController;
use App\Domain\Kepatuhan\Http\Controllers\IntegrasiEksternalController;
use App\Domain\Sinkronisasi\Http\Controllers\AntrianSinkronisasiController;
use App\Domain\Sinkronisasi\Http\Controllers\OfflineTeknisiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi', 'fitur:modul.integrasi'])
    ->prefix('integrasi')
    ->name('integrasi.')
    ->group(function (): void {
        Route::get('/', [IntegrasiEksternalController::class, 'index'])->name('index');
        Route::post('/', [IntegrasiEksternalController::class, 'store'])->name('store');
        Route::get('/{integrasi}', [IntegrasiEksternalController::class, 'show'])->name('show');
        Route::put('/{integrasi}', [IntegrasiEksternalController::class, 'update'])->name('update');
        Route::delete('/{integrasi}', [IntegrasiEksternalController::class, 'destroy'])->name('destroy');
        Route::post('/{integrasi}/status', [IntegrasiEksternalController::class, 'ubahStatus'])->name('status');
        Route::post('/{integrasi}/uji-koneksi', [IntegrasiEksternalController::class, 'ujiKoneksi'])->name('uji-koneksi');

        Route::post('/{integrasi}/sinkronisasi', [IntegrasiEksternalController::class, 'sinkronkan'])->name('sinkronkan');

        Route::post('/{integrasi}/pemetaan', [IntegrasiEksternalController::class, 'storePemetaan'])->name('pemetaan.store');
        Route::post('/{integrasi}/pemetaan/{pemetaanDataEksternal}/konflik', [IntegrasiEksternalController::class, 'selesaikanKonflik'])->name('pemetaan.konflik');
        Route::delete('/{integrasi}/pemetaan/{pemetaanDataEksternal}', [IntegrasiEksternalController::class, 'destroyPemetaan'])->name('pemetaan.destroy');

        Route::post('/panggilan-balik', [PanggilanBalikWebController::class, 'store'])->name('panggilan-balik.store');
        Route::put('/panggilan-balik/{panggilanBalikWeb}', [PanggilanBalikWebController::class, 'update'])->name('panggilan-balik.update');
        Route::delete('/panggilan-balik/{panggilanBalikWeb}', [PanggilanBalikWebController::class, 'destroy'])->name('panggilan-balik.destroy');
        Route::get('/panggilan-balik/{panggilanBalikWeb}/pengiriman', [PanggilanBalikWebController::class, 'riwayat'])->name('panggilan-balik.pengiriman');
    });

// | Jalur PWA offline teknisi (FASE 20).
Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('offline')
    ->name('offline.')
    ->group(function (): void {
        Route::get('/teknisi', [OfflineTeknisiController::class, 'index'])->name('teknisi');
        Route::post('/paket', [OfflineTeknisiController::class, 'paket'])->name('paket');
        Route::get('/ringkasan', [OfflineTeknisiController::class, 'ringkasan'])->name('ringkasan');
        Route::post('/perangkat/lepas', [OfflineTeknisiController::class, 'lepaskanPerangkat'])->name('perangkat.lepas');

        Route::post('/antrian', [AntrianSinkronisasiController::class, 'dorong'])->name('antrian.dorong');
        Route::post('/antrian/status', [AntrianSinkronisasiController::class, 'status'])->name('antrian.status');
        Route::post('/antrian/{antrianSinkronisasi}/konflik', [AntrianSinkronisasiController::class, 'selesaikanKonflik'])->name('antrian.konflik');
    });
