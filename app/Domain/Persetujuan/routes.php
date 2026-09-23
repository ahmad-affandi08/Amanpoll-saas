<?php

declare(strict_types=1);

use App\Domain\Persetujuan\Http\Controllers\AlurPersetujuanController;
use App\Domain\Persetujuan\Http\Controllers\PermintaanPersetujuanController;
use App\Domain\Persetujuan\Http\Controllers\TahapPersetujuanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('persetujuan')
    ->name('persetujuan.')
    ->group(function (): void {
        Route::get('/alur', [AlurPersetujuanController::class, 'index'])->name('alur.index');
        Route::get('/alur/ekspor', [AlurPersetujuanController::class, 'ekspor'])->middleware('throttle:ekspor')->name('alur.ekspor');
        Route::post('/alur', [AlurPersetujuanController::class, 'store'])->name('alur.store');
        Route::put('/alur/{alurPersetujuan}', [AlurPersetujuanController::class, 'update'])->name('alur.update');
        Route::delete('/alur/{alurPersetujuan}', [AlurPersetujuanController::class, 'destroy'])->name('alur.destroy');
        Route::post('/alur/{alurPersetujuan}/aktifkan', [AlurPersetujuanController::class, 'aktifkan'])->name('alur.aktifkan');
        Route::post('/alur/{alurPersetujuan}/nonaktifkan', [AlurPersetujuanController::class, 'nonaktifkan'])->name('alur.nonaktifkan');

        Route::post('/alur/{alurPersetujuan}/tahap', [TahapPersetujuanController::class, 'store'])->name('tahap.store');
        Route::put('/tahap/{tahapPersetujuan}', [TahapPersetujuanController::class, 'update'])->name('tahap.update');
        Route::delete('/tahap/{tahapPersetujuan}', [TahapPersetujuanController::class, 'destroy'])->name('tahap.destroy');

        Route::get('/permintaan', [PermintaanPersetujuanController::class, 'halaman'])->name('permintaan.halaman');
        Route::get('/permintaan/milik-saya', [PermintaanPersetujuanController::class, 'milikSaya'])->name('permintaan.milikSaya');
        Route::get('/permintaan/inbox', [PermintaanPersetujuanController::class, 'inbox'])->name('permintaan.inbox');
        Route::post('/permintaan', [PermintaanPersetujuanController::class, 'store'])->name('permintaan.store');
        Route::delete('/permintaan/{permintaanPersetujuan}', [PermintaanPersetujuanController::class, 'destroy'])->name('permintaan.destroy');
        Route::post('/permintaan/{permintaanPersetujuan}/setujui', [PermintaanPersetujuanController::class, 'setujui'])->name('permintaan.setujui');
        Route::post('/permintaan/{permintaanPersetujuan}/tolak', [PermintaanPersetujuanController::class, 'tolak'])->name('permintaan.tolak');
    });
