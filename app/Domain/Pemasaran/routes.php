<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Http\Controllers\AturanSkorProspekController;
use App\Domain\Pemasaran\Http\Controllers\FormulirPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\HalamanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\ImporEksporProspekController;
use App\Domain\Pemasaran\Http\Controllers\KampanyeController;
use App\Domain\Pemasaran\Http\Controllers\PengaturanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\ProspekController;
use App\Domain\Pemasaran\Http\Controllers\RedirectPemasaranController;
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

        // CRM prospek. Seluruhnya di balik flag marketing.crm, sehingga
        // mematikan modulnya benar-benar menutup datanya.
        Route::middleware('fitur.platform:'.KatalogFiturPlatform::CRM)
            ->prefix('prospek')
            ->name('prospek.')
            ->group(function (): void {
                /*
                 * Aturan bobot skor. Didaftarkan sebelum `/{prospek}` karena
                 * jalurnya akan tertelan parameter itu — Laravel memilih rute
                 * pertama yang cocok, dan `aturan-skor` adalah ULID yang sah
                 * bagi pencocoknya.
                 *
                 * Mengubah bobot menggeser seluruh prioritas tim penjualan,
                 * jadi haknya izin kelola dan setiap perubahannya tercatat di
                 * audit.
                 */
                Route::prefix('aturan-skor')->name('aturanSkor.')->group(function (): void {
                    Route::get('/', [AturanSkorProspekController::class, 'index'])
                        ->middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_LIHAT)
                        ->name('index');

                    Route::middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_KELOLA)
                        ->group(function (): void {
                            Route::post('/', [AturanSkorProspekController::class, 'store'])->name('store');
                            Route::put('/{aturan}', [AturanSkorProspekController::class, 'update'])
                                ->name('update');
                            Route::delete('/{aturan}', [AturanSkorProspekController::class, 'destroy'])
                                ->name('destroy');
                        });
                });

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_LIHAT)->group(function (): void {
                    Route::get('/', [ProspekController::class, 'index'])->name('index');
                    Route::get('/{prospek}', [ProspekController::class, 'show'])->name('show');
                });

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_KELOLA)->group(function (): void {
                    Route::post('/', [ProspekController::class, 'store'])->name('store');
                    Route::post('/{prospek}/tahap', [ProspekController::class, 'pindahkanTahap'])->name('tahap');
                    Route::post('/{prospek}/aktivitas', [ProspekController::class, 'catatAktivitas'])
                        ->name('aktivitas');
                    Route::post('/impor', [ImporEksporProspekController::class, 'impor'])->name('impor');
                });

                // Ekspor memindahkan data pribadi keluar sistem: izinnya sendiri
                // dan lajunya dibatasi (MARKETING.md 26, 27).
                Route::get('/ekspor/csv', [ImporEksporProspekController::class, 'ekspor'])
                    ->middleware([
                        'izin.platform:'.KatalogIzinPemasaran::PROSPEK_EKSPOR,
                        'throttle:ekspor',
                    ])
                    ->name('ekspor');
            });

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

        /*
         * Landing page builder dan peta redirect. Keduanya di balik flag
         * marketing.cms: mematikan modulnya menutup konsolnya, sementara
         * halaman yang sudah terbit tetap dilayani host publik — menurunkan
         * situs pemasaran bukan yang diminta siapa pun ketika mematikan
         * konsol penyuntingnya.
         */
        Route::middleware('fitur.platform:'.KatalogFiturPlatform::CMS)->group(function (): void {
            Route::prefix('halaman')->name('halaman.')->group(function (): void {
                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_LIHAT)->group(function (): void {
                    Route::get('/', [HalamanPemasaranController::class, 'index'])->name('index');
                    Route::get('/baru', [HalamanPemasaranController::class, 'create'])->name('create');
                    Route::get('/{halaman}', [HalamanPemasaranController::class, 'edit'])->name('edit');
                });

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_KELOLA)->group(function (): void {
                    Route::post('/', [HalamanPemasaranController::class, 'store'])->name('store');
                    Route::put('/{halaman}', [HalamanPemasaranController::class, 'update'])->name('update');
                    Route::get('/{halaman}/pratinjau/{versi}', [HalamanPemasaranController::class, 'pratinjau'])
                        ->name('pratinjau');
                });

                /*
                 * Menerbitkan, menjadwalkan, dan mengembalikan mengubah isi
                 * situs publik, jadi ketiganya menuntut izin terbit — bukan
                 * izin kelola (MARKETING.md 26).
                 */
                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_TERBITKAN)
                    ->group(function (): void {
                        Route::post('/{halaman}/terbitkan', [HalamanPemasaranController::class, 'terbitkan'])
                            ->name('terbitkan');
                        Route::post('/{halaman}/status', [HalamanPemasaranController::class, 'ubahStatus'])
                            ->name('status');
                        Route::post('/{halaman}/kembalikan/{versi}', [
                            HalamanPemasaranController::class, 'kembalikan',
                        ])->name('kembalikan');
                    });
            });

            Route::prefix('formulir')->name('formulir.')->group(function (): void {
                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_LIHAT)->group(function (): void {
                    Route::get('/', [FormulirPemasaranController::class, 'index'])->name('index');
                    Route::get('/{formulir}', [FormulirPemasaranController::class, 'show'])->name('show');
                });

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_KELOLA)->group(function (): void {
                    Route::post('/', [FormulirPemasaranController::class, 'store'])->name('store');
                    Route::put('/{formulir}', [FormulirPemasaranController::class, 'update'])->name('update');
                });
            });

            Route::prefix('redirect')->name('redirect.')->group(function (): void {
                Route::get('/', [RedirectPemasaranController::class, 'index'])
                    ->middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_LIHAT)
                    ->name('index');

                // Redirect mengubah alamat yang dilihat mesin pencari, jadi
                // haknya sama dengan menerbitkan halaman.
                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_TERBITKAN)
                    ->group(function (): void {
                        Route::post('/', [RedirectPemasaranController::class, 'store'])->name('store');
                        Route::put('/{redirect}', [RedirectPemasaranController::class, 'update'])->name('update');
                        Route::delete('/{redirect}', [RedirectPemasaranController::class, 'destroy'])
                            ->name('destroy');
                    });
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
