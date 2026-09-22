<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
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
