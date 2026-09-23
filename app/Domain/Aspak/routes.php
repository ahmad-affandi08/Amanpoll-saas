<?php

declare(strict_types=1);

use App\Domain\Aspak\Http\Controllers\AspakController;
use Illuminate\Support\Facades\Route;

/*
 * Tidak dipagari flag fitur seperti Kalibrasi atau Kepatuhan: ASPAK adalah
 * kewajiban pelaporan ke Kemenkes bagi rumah sakit yang memakainya, bukan
 * modul tambahan yang dijual terpisah. Aksesnya dijaga izin Aspak.Kelola.
 */
Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('aspak')
    ->name('aspak.')
    ->group(function (): void {
        Route::get('/', [AspakController::class, 'index'])->name('index');
        Route::get('/ekspor', [AspakController::class, 'ekspor'])->middleware('throttle:ekspor')->name('ekspor');
        Route::get('/katalog/cari', [AspakController::class, 'cariKatalog'])->name('katalog.cari');
        Route::post('/katalog/impor', [AspakController::class, 'impor'])->name('katalog.impor');
        Route::post('/pemetaan', [AspakController::class, 'simpanPemetaan'])->name('pemetaan.store');
        Route::delete('/pemetaan/{pemetaan}', [AspakController::class, 'hapusPemetaan'])->name('pemetaan.destroy');
    });
