<?php

declare(strict_types=1);

use App\Domain\Penyedia\Http\Controllers\KategoriPenyediaController;
use App\Domain\Penyedia\Http\Controllers\KontakPenyediaController;
use App\Domain\Penyedia\Http\Controllers\PenilaianPenyediaController;
use App\Domain\Penyedia\Http\Controllers\PenyediaController;
use App\Domain\Penyedia\Http\Controllers\PenyediaKategoriController;
use App\Domain\Penyedia\Http\Controllers\RiwayatPenyediaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('penyedia')
    ->name('penyedia.')
    ->group(function (): void {
        Route::get('/', [PenyediaController::class, 'index'])->name('index');
        Route::post('/', [PenyediaController::class, 'store'])->name('store');
        Route::put('/{penyedia}', [PenyediaController::class, 'update'])->name('update');
        Route::delete('/{penyedia}', [PenyediaController::class, 'destroy'])->name('destroy');

        Route::post('/kategori', [KategoriPenyediaController::class, 'store'])->name('kategori.store');
        Route::put('/kategori/{kategoriPenyedia}', [KategoriPenyediaController::class, 'update'])->name('kategori.update');
        Route::delete('/kategori/{kategoriPenyedia}', [KategoriPenyediaController::class, 'destroy'])->name('kategori.destroy');

        Route::post('/{penyedia}/kategori', [PenyediaKategoriController::class, 'store'])->name('penyediaKategori.store');
        Route::delete('/{penyedia}/kategori/{kategoriPenyedia}', [PenyediaKategoriController::class, 'destroy'])->name('penyediaKategori.destroy');

        Route::get('/{penyedia}/kontak', [KontakPenyediaController::class, 'index'])->name('kontak.index');
        Route::post('/{penyedia}/kontak', [KontakPenyediaController::class, 'store'])->name('kontak.store');
        Route::put('/kontak/{kontakPenyedia}', [KontakPenyediaController::class, 'update'])->name('kontak.update');
        Route::delete('/kontak/{kontakPenyedia}', [KontakPenyediaController::class, 'destroy'])->name('kontak.destroy');

        Route::get('/{penyedia}/penilaian', [PenilaianPenyediaController::class, 'index'])->name('penilaian.index');
        Route::post('/{penyedia}/penilaian', [PenilaianPenyediaController::class, 'store'])->name('penilaian.store');

        Route::get('/{penyedia}/riwayat-pengadaan', [RiwayatPenyediaController::class, 'pengadaan'])->name('riwayatPengadaan');
        Route::get('/{penyedia}/riwayat-layanan', [RiwayatPenyediaController::class, 'layanan'])->name('riwayatLayanan');

        // Paling akhir supaya '/kategori' dan '/kontak' tidak tertelan '{penyedia}'.
        Route::get('/{penyedia}', [PenyediaController::class, 'show'])->name('show');
    });
