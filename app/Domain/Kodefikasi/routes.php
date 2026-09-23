<?php

declare(strict_types=1);

use App\Domain\Kodefikasi\Http\Controllers\KodefikasiController;
use Illuminate\Support\Facades\Route;

/*
 * Tidak dipagari flag fitur: kodefikasi barang milik negara/daerah adalah
 * kewajiban penatausahaan bagi rumah sakit pemerintah, bukan modul tambahan.
 * Aksesnya dijaga izin Aset.Lihat untuk membaca dan Aset.Ubah untuk mengubah.
 */
Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('kodefikasi')
    ->name('kodefikasi.')
    ->group(function (): void {
        Route::get('/', [KodefikasiController::class, 'index'])->name('index');
        Route::get('/ekspor', [KodefikasiController::class, 'ekspor'])->middleware('throttle:ekspor')->name('ekspor');
        Route::get('/katalog/cari', [KodefikasiController::class, 'cariKatalog'])->name('katalog.cari');
        Route::post('/katalog/impor', [KodefikasiController::class, 'impor'])->name('katalog.impor');
        Route::post('/penetapan', [KodefikasiController::class, 'tetapkan'])->name('penetapan.store');
        Route::get('/aset/{aset}', [KodefikasiController::class, 'untukAset'])->name('aset');
    });
