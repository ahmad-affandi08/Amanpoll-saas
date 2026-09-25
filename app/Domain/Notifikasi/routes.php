<?php

declare(strict_types=1);

use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Notifikasi\Http\Controllers\EmailUjiPenyediaController;
use App\Domain\Notifikasi\Http\Controllers\NotifikasiController;
use App\Domain\Notifikasi\Http\Controllers\PengirimNotifikasiController;
use App\Domain\Notifikasi\Http\Controllers\PreferensiNotifikasiController;
use App\Domain\Notifikasi\Http\Controllers\TemplatNotifikasiController;
use App\Domain\Platform\Application\Services\KatalogPenyediaLayanan;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'organisasi'])
    ->prefix('notifikasi')
    ->name('notifikasi.')
    ->group(function (): void {
        Route::get('/templat', [TemplatNotifikasiController::class, 'index'])->name('templat.index');
        Route::get('/templat/ekspor', [TemplatNotifikasiController::class, 'ekspor'])->middleware('throttle:ekspor')->name('templat.ekspor');
        Route::post('/templat', [TemplatNotifikasiController::class, 'store'])->name('templat.store');
        Route::put('/templat/{templatNotifikasi}', [TemplatNotifikasiController::class, 'update'])->name('templat.update');
        Route::delete('/templat/{templatNotifikasi}', [TemplatNotifikasiController::class, 'destroy'])->name('templat.destroy');

        Route::get('/preferensi', [PreferensiNotifikasiController::class, 'halaman'])->name('preferensi.halaman');
        Route::get('/preferensi/data', [PreferensiNotifikasiController::class, 'index'])->name('preferensi.index');
        Route::post('/preferensi', [PreferensiNotifikasiController::class, 'store'])->name('preferensi.store');

        // Email & WhatsApp milik organisasi (PRD 8.23). Halamannya terbuka untuk semua paket supaya
        // kuota WhatsApp bawaan tetap terlihat; mengubah dan menguji butuh fitur paketnya.
        Route::get('/email-whatsapp', [PengirimNotifikasiController::class, 'index'])->name('pengirim.index');
        Route::delete('/email-whatsapp/{kategori}/{kode}', [PengirimNotifikasiController::class, 'hapus'])->name('pengirim.hapus');
        Route::middleware('fitur:'.KatalogFitur::LAYANAN_PENYEDIA_SENDIRI)->group(function (): void {
            Route::put('/email-whatsapp/{kategori}/{kode}', [PengirimNotifikasiController::class, 'simpan'])->name('pengirim.simpan');
            Route::post('/email-whatsapp/{kategori}/{kode}/uji', [PengirimNotifikasiController::class, 'uji'])
                ->middleware('throttle:6,1')
                ->name('pengirim.uji');
            Route::post('/email-whatsapp/{kategori}/{kode}/kirim-uji', [PengirimNotifikasiController::class, 'kirimUji'])
                ->middleware('throttle:6,1')
                ->name('pengirim.kirimUji');
        });

        Route::get('/ringkasan', [NotifikasiController::class, 'ringkasan'])->name('ringkasan');
        Route::post('/{notifikasi}/baca', [NotifikasiController::class, 'baca'])->name('baca');
        Route::post('/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('bacaSemua');
    });

// Email uji dari konsol penyedia layanan platform, memakai kredensial tersimpan walau belum aktif (PRD 8.23).
Route::middleware(['web', 'auth:platform', 'izin.platform:'.KatalogPenyediaLayanan::IZIN_KELOLA, 'throttle:6,1'])
    ->post('admin-platform/penyedia-layanan/Email/{kode}/kirim-uji', [EmailUjiPenyediaController::class, 'kirim'])
    ->name('adminPlatform.penyedia-layanan.email.kirim-uji');
