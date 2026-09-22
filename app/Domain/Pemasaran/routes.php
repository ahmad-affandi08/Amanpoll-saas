<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Http\Controllers\KampanyeController;
use App\Domain\Pemasaran\Http\Controllers\PengaturanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\RingkasanPemasaranController;
use Illuminate\Support\Facades\Route;

/*
 * Konsol Growth & Marketing (MARKETING.md 4).
 *
 * Berada di bawah guard `platform`, bukan guard tenant: yang dikelola di sini
 * adalah pemasaran Amanpoll sendiri, bukan data satu pelanggan. Host dashboard
 * sudah dipasang DomainServiceProvider untuk seluruh berkas rute domain.
 */
Route::middleware(['web', 'auth:platform'])
    ->prefix('admin-platform/pemasaran')
    ->name('pemasaran.')
    ->group(function (): void {
        Route::get('/', RingkasanPemasaranController::class)
            ->middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_LIHAT)
            ->name('ringkasan');

        // Kampanye berada di balik flag analitik: tanpa modulnya hidup, tidak
        // ada tempat angkanya dibaca.
        Route::middleware([
            'izin.platform:'.KatalogIzinPemasaran::KAMPANYE_LIHAT,
            'fitur.platform:'.KatalogFiturPlatform::ANALITIK,
        ])->prefix('kampanye')->name('kampanye.')->group(function (): void {
            Route::get('/', [KampanyeController::class, 'index'])->name('index');

            Route::middleware('izin.platform:'.KatalogIzinPemasaran::KAMPANYE_KELOLA)->group(function (): void {
                Route::post('/', [KampanyeController::class, 'store'])->name('store');
                Route::put('/{kampanye}', [KampanyeController::class, 'update'])->name('update');
            });
        });

        Route::middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_KELOLA)
            ->prefix('pengaturan')
            ->name('pengaturan.')
            ->group(function (): void {
                Route::get('/', [PengaturanPemasaranController::class, 'index'])->name('index');
                Route::post('/fitur', [PengaturanPemasaranController::class, 'ubahFitur'])->name('fitur');
                Route::post('/konfigurasi', [PengaturanPemasaranController::class, 'simpanKonfigurasi'])
                    ->name('konfigurasi');
            });
    });
