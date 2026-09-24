<?php

declare(strict_types=1);

use App\Domain\IntegrasiAudit\Http\Controllers\PanggilanBalikWebController;
use App\Domain\Kepatuhan\Http\Controllers\IntegrasiEksternalController;
use App\Domain\Sinkronisasi\Http\Controllers\AntrianSinkronisasiController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganAkunController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganBerandaController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganNotifikasiController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporAsetController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporBerandaController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporKonfirmasiController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporLaporanController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporLaporController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganPelaporPantauController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTampilanController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiAsetController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiBerandaController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiKonflikController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiPindaiController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiSiapkanController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiSukuCadangController;
use App\Domain\Sinkronisasi\Http\Controllers\LapanganTeknisiTugasController;
use App\Domain\Sinkronisasi\Http\Controllers\OfflineTeknisiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi', 'fitur:modul.integrasi'])
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

// | Jalur PWA offline teknisi (FASE 20).
Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('offline')
    ->name('offline.')
    ->group(function (): void {
        // Jalur lama; dialihkan ke Mode Lapangan (PRD 8.20).
        Route::get('/teknisi', [OfflineTeknisiController::class, 'index'])->name('teknisi');
        Route::post('/paket', [OfflineTeknisiController::class, 'paket'])->name('paket');
        Route::get('/ringkasan', [OfflineTeknisiController::class, 'ringkasan'])->name('ringkasan');
        Route::post('/perangkat/lepas', [OfflineTeknisiController::class, 'lepaskanPerangkat'])->name('perangkat.lepas');

        Route::post('/antrian', [AntrianSinkronisasiController::class, 'dorong'])->name('antrian.dorong');
        Route::post('/antrian/status', [AntrianSinkronisasiController::class, 'status'])->name('antrian.status');
        Route::post('/antrian/{antrianSinkronisasi}/konflik', [AntrianSinkronisasiController::class, 'selesaikanKonflik'])->name('antrian.konflik');
    });

// | Mode Lapangan untuk Teknisi dan Pelapor (FASE 39, PRD 8.20, DESIGN §36).
// Controller di sini hanya menyusun data layar dan memanggil Action domain
// pemiliknya; otorisasi memakai policy yang sama dengan dasbor.
Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('lapangan')
    ->name('lapangan.')
    ->group(function (): void {
        // Pilihan tampilan pengguna campuran; pagarnya ada di controller.
        Route::post('/tampilan', LapanganTampilanController::class)->name('tampilan');

        Route::middleware('mode.lapangan')->group(function (): void {
            Route::get('/', LapanganBerandaController::class)->name('beranda');
            Route::get('/notifikasi', LapanganNotifikasiController::class)->name('notifikasi');
            Route::get('/akun', LapanganAkunController::class)->name('akun');
        });

        Route::middleware('mode.lapangan:Teknisi')
            ->prefix('teknisi')
            ->name('teknisi.')
            ->group(function (): void {
                Route::get('/', LapanganTeknisiBerandaController::class)->name('beranda');

                // === Teknisi (C) ===
                Route::get('/siapkan', LapanganTeknisiSiapkanController::class)->name('siapkan');
                Route::get('/tugas', [LapanganTeknisiTugasController::class, 'index'])->name('tugas');
                Route::get('/tugas/{perintahKerja}', [LapanganTeknisiTugasController::class, 'show'])->name('tugas.show');
                Route::get('/tugas/{perintahKerja}/kerjakan', [LapanganTeknisiTugasController::class, 'kerjakan'])->name('tugas.kerjakan');
                Route::get('/tugas/{perintahKerja}/suku-cadang', [LapanganTeknisiSukuCadangController::class, 'cari'])->name('tugas.suku-cadang');
                Route::get('/suku-cadang', [LapanganTeknisiSukuCadangController::class, 'index'])->name('suku-cadang');
                // `pindai?aset=<AsetId>` adalah tujuan pengalihan `aset.pindai` untuk pengguna mode Teknisi.
                Route::get('/pindai', LapanganTeknisiPindaiController::class)->name('pindai');
                Route::get('/aset', [LapanganTeknisiAsetController::class, 'index'])->name('aset');
                Route::get('/aset/{aset}/riwayat', [LapanganTeknisiAsetController::class, 'riwayat'])->name('aset.riwayat');
                Route::get('/konflik/{kunci}', LapanganTeknisiKonflikController::class)->name('konflik');
            });

        // Teknisi juga boleh melapor lewat aksi cepat, jadi layar pelapor terbuka bagi keduanya.
        Route::middleware('mode.lapangan:Pelapor,Teknisi')
            ->prefix('pelapor')
            ->name('pelapor.')
            ->group(function (): void {
                Route::get('/', LapanganPelaporBerandaController::class)->name('beranda');

                // === Pelapor (D) ===
                // `lapor?aset=<AsetId>` adalah kontrak tetap hasil pindai QR pengguna mode Pelapor.
                Route::get('/lapor', [LapanganPelaporLaporController::class, 'create'])->name('lapor');
                Route::post('/lapor', [LapanganPelaporLaporController::class, 'store'])->name('lapor.store');
                Route::get('/laporan', [LapanganPelaporLaporanController::class, 'index'])->name('laporan');
                Route::get('/laporan/{keluhan}', [LapanganPelaporLaporanController::class, 'show'])->name('laporan.show');
                Route::get('/laporan/{keluhan}/terkirim', [LapanganPelaporLaporanController::class, 'terkirim'])->name('laporan.terkirim');
                Route::get('/laporan/{keluhan}/konfirmasi', [LapanganPelaporKonfirmasiController::class, 'create'])->name('laporan.konfirmasi');
                Route::post('/laporan/{keluhan}/konfirmasi', [LapanganPelaporKonfirmasiController::class, 'store'])->name('laporan.konfirmasi.store');
                Route::get('/laporan/{keluhan}/terima-kasih', [LapanganPelaporKonfirmasiController::class, 'terimaKasih'])->name('laporan.terima-kasih');
                Route::get('/aset', LapanganPelaporAsetController::class)->name('aset');
                // Garis waktu status keluhan rekan dalam lingkup pelapor (PRD 8.20).
                Route::get('/pantau/{keluhan}', LapanganPelaporPantauController::class)->name('pantau');
            });
    });
