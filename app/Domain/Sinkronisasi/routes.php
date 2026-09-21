<?php

declare(strict_types=1);

use App\Domain\IntegrasiAudit\Http\Controllers\PanggilanBalikWebController;
use App\Domain\Kepatuhan\Http\Controllers\IntegrasiEksternalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
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
