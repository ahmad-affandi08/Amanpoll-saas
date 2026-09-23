<?php

declare(strict_types=1);

use App\Domain\Kepatuhan\Http\Controllers\KepatuhanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi', 'fitur:modul.kepatuhan'])
    ->prefix('kepatuhan')
    ->name('kepatuhan.')
    ->group(function (): void {
        Route::get('/', [KepatuhanController::class, 'index'])->name('index');
        Route::get('/ekspor', [KepatuhanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('ekspor');

        Route::post('/standar', [KepatuhanController::class, 'storeStandar'])->name('standar.store');
        Route::get('/standar/{standarKepatuhan}', [KepatuhanController::class, 'showStandar'])->name('standar.show');
        Route::put('/standar/{standarKepatuhan}', [KepatuhanController::class, 'updateStandar'])->name('standar.update');
        Route::delete('/standar/{standarKepatuhan}', [KepatuhanController::class, 'destroyStandar'])->name('standar.destroy');
        Route::post('/standar/{standarKepatuhan}/persyaratan', [KepatuhanController::class, 'storePersyaratan'])->name('persyaratan.store');
        Route::delete('/standar/{standarKepatuhan}/persyaratan/{persyaratanKepatuhan}', [KepatuhanController::class, 'destroyPersyaratan'])->name('persyaratan.destroy');

        Route::post('/tugaskan', [KepatuhanController::class, 'tugaskanStandar'])->name('tugaskan');
        Route::post('/kewajiban/{kepatuhanAset}/pemeriksaan', [KepatuhanController::class, 'catatPemeriksaan'])->name('pemeriksaan');
        Route::delete('/kewajiban/{kepatuhanAset}', [KepatuhanController::class, 'destroyKepatuhan'])->name('kewajiban.destroy');

        Route::get('/sertifikasi', [KepatuhanController::class, 'indexSertifikasi'])->name('sertifikasi.index');
        Route::post('/sertifikasi', [KepatuhanController::class, 'storeSertifikasi'])->name('sertifikasi.store');
        Route::put('/sertifikasi/{sertifikasiAset}', [KepatuhanController::class, 'updateSertifikasi'])->name('sertifikasi.update');
        Route::post('/sertifikasi/{sertifikasiAset}/cabut', [KepatuhanController::class, 'cabutSertifikasi'])->name('sertifikasi.cabut');
    });
