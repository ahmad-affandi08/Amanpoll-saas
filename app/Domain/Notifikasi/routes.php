<?php

declare(strict_types=1);

use App\Domain\Notifikasi\Http\Controllers\NotifikasiController;
use App\Domain\Notifikasi\Http\Controllers\PreferensiNotifikasiController;
use App\Domain\Notifikasi\Http\Controllers\TemplatNotifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('notifikasi')
    ->name('notifikasi.')
    ->group(function (): void {
        Route::get('/templat', [TemplatNotifikasiController::class, 'index'])->name('templat.index');
        Route::post('/templat', [TemplatNotifikasiController::class, 'store'])->name('templat.store');
        Route::put('/templat/{templatNotifikasi}', [TemplatNotifikasiController::class, 'update'])->name('templat.update');
        Route::delete('/templat/{templatNotifikasi}', [TemplatNotifikasiController::class, 'destroy'])->name('templat.destroy');

        Route::get('/preferensi', [PreferensiNotifikasiController::class, 'halaman'])->name('preferensi.halaman');
        Route::get('/preferensi/data', [PreferensiNotifikasiController::class, 'index'])->name('preferensi.index');
        Route::post('/preferensi', [PreferensiNotifikasiController::class, 'store'])->name('preferensi.store');

        Route::get('/ringkasan', [NotifikasiController::class, 'ringkasan'])->name('ringkasan');
        Route::post('/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('bacaSemua');
    });
