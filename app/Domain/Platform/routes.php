<?php

declare(strict_types=1);

use App\Domain\Platform\Http\Controllers\DokumentasiController;
use App\Domain\Platform\Http\Controllers\HariLiburController;
use App\Domain\Platform\Http\Controllers\IzinController;
use App\Domain\Platform\Http\Controllers\KategoriLokasiController;
use App\Domain\Platform\Http\Controllers\KonfigurasiOrganisasiController;
use App\Domain\Platform\Http\Controllers\KunciApiController;
use App\Domain\Platform\Http\Controllers\LokasiController;
use App\Domain\Platform\Http\Controllers\NomorDokumenController;
use App\Domain\Platform\Http\Controllers\OrganisasiController;
use App\Domain\Platform\Http\Controllers\PenggunaController;
use App\Domain\Platform\Http\Controllers\PenggunaPeranController;
use App\Domain\Platform\Http\Controllers\PeranController;
use App\Domain\Platform\Http\Controllers\ProfilController;
use App\Domain\Platform\Http\Controllers\RiwayatPenggunaController;
use App\Domain\Platform\Http\Controllers\UnitOrganisasiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function (): void {
        Route::get('/izin', [IzinController::class, 'index'])->name('izin.index');

        Route::get('/peran', [PeranController::class, 'index'])->name('peran.index');
        Route::post('/peran', [PeranController::class, 'store'])->name('peran.store');
        Route::post('/peran/bawaan', [PeranController::class, 'pasangBawaan'])->name('peran.bawaan');
        Route::put('/peran/{peran}', [PeranController::class, 'update'])->name('peran.update');
        Route::delete('/peran/{peran}', [PeranController::class, 'destroy'])->name('peran.destroy');
        Route::put('/peran/{peran}/izin', [PeranController::class, 'sinkronkanIzin'])->name('peran.izin');

        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{pengguna}', [PenggunaController::class, 'show'])->name('pengguna.show');
        Route::get('/pengguna/{pengguna}/beban-kerja', [RiwayatPenggunaController::class, 'bebanKerja'])->name('pengguna.bebanKerja');
        Route::get('/pengguna/{pengguna}/aktivitas', [RiwayatPenggunaController::class, 'aktivitas'])->name('pengguna.aktivitas');
        Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::put('/pengguna/{pengguna}/status', [PenggunaController::class, 'ubahStatus'])->name('pengguna.status');
        Route::post('/pengguna/{pengguna}/peran', [PenggunaPeranController::class, 'store'])->name('pengguna.peran.store');
        Route::delete('/pengguna-peran/{penggunaPeran}', [PenggunaPeranController::class, 'destroy'])->name('pengguna.peran.destroy');

        Route::get('/profil', [ProfilController::class, 'edit'])->name('profil.edit');
        Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
        Route::put('/profil/kata-sandi', [ProfilController::class, 'gantiKataSandi'])->name('profil.kata-sandi');
        Route::delete('/profil/perangkat/{perangkat}', [ProfilController::class, 'hapusPerangkat'])->name('profil.perangkat.destroy');

        // Kunci API adalah pintu masuk integrasi, jadi ikut gerbang fitur yang sama dengan modul integrasi (22.05).
        Route::middleware('fitur:modul.integrasi')->group(function (): void {
            Route::get('/kunci-api', [KunciApiController::class, 'index'])->name('kunci-api.index');
            Route::post('/kunci-api', [KunciApiController::class, 'store'])->name('kunci-api.store');
            Route::delete('/kunci-api/{kunciApi}', [KunciApiController::class, 'destroy'])->name('kunci-api.destroy');
        });

        Route::get('/organisasi', [OrganisasiController::class, 'edit'])->name('organisasi.edit');
        Route::put('/organisasi', [OrganisasiController::class, 'update'])->name('organisasi.update');
        Route::post('/organisasi/logo', [OrganisasiController::class, 'unggahLogo'])->name('organisasi.logo');

        Route::get('/unit-organisasi', [UnitOrganisasiController::class, 'index'])->name('unit-organisasi.index');
        Route::post('/unit-organisasi', [UnitOrganisasiController::class, 'store'])->name('unit-organisasi.store');
        Route::put('/unit-organisasi/{unit}', [UnitOrganisasiController::class, 'update'])->name('unit-organisasi.update');
        Route::delete('/unit-organisasi/{unit}', [UnitOrganisasiController::class, 'destroy'])->name('unit-organisasi.destroy');

        Route::get('/kategori-lokasi', [KategoriLokasiController::class, 'index'])->name('kategori-lokasi.index');
        Route::post('/kategori-lokasi', [KategoriLokasiController::class, 'store'])->name('kategori-lokasi.store');
        Route::put('/kategori-lokasi/{kategoriLokasi}', [KategoriLokasiController::class, 'update'])->name('kategori-lokasi.update');
        Route::delete('/kategori-lokasi/{kategoriLokasi}', [KategoriLokasiController::class, 'destroy'])->name('kategori-lokasi.destroy');

        Route::get('/lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
        Route::post('/lokasi', [LokasiController::class, 'store'])->name('lokasi.store');
        Route::put('/lokasi/{lokasi}', [LokasiController::class, 'update'])->name('lokasi.update');
        Route::delete('/lokasi/{lokasi}', [LokasiController::class, 'destroy'])->name('lokasi.destroy');

        Route::get('/konfigurasi', [KonfigurasiOrganisasiController::class, 'index'])->name('konfigurasi.index');
        Route::put('/konfigurasi/{kunci}', [KonfigurasiOrganisasiController::class, 'update'])->name('konfigurasi.update');

        Route::get('/nomor-dokumen', [NomorDokumenController::class, 'index'])->name('nomor-dokumen.index');
        Route::post('/nomor-dokumen', [NomorDokumenController::class, 'store'])->name('nomor-dokumen.store');
        Route::put('/nomor-dokumen/{nomorDokumen}', [NomorDokumenController::class, 'update'])->name('nomor-dokumen.update');
        Route::delete('/nomor-dokumen/{nomorDokumen}', [NomorDokumenController::class, 'destroy'])->name('nomor-dokumen.destroy');

        Route::get('/hari-libur', [HariLiburController::class, 'index'])->name('hari-libur.index');
        Route::post('/hari-libur', [HariLiburController::class, 'store'])->name('hari-libur.store');
        Route::put('/hari-libur/{hariLibur}', [HariLiburController::class, 'update'])->name('hari-libur.update');
        Route::delete('/hari-libur/{hariLibur}', [HariLiburController::class, 'destroy'])->name('hari-libur.destroy');
    });

// Panduan pemakaian; di luar prefix platform karena bukan halaman pengaturan.
Route::middleware(['web', 'auth', 'organisasi'])->group(function (): void {
    Route::get('/dokumentasi', DokumentasiController::class)->name('dokumentasi.index');
    Route::get('/dokumentasi/{halaman}', DokumentasiController::class)->name('dokumentasi.halaman');
});
