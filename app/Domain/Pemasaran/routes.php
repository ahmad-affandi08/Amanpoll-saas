<?php

declare(strict_types=1);

use App\Domain\Pemasaran\Domain\KatalogFiturPlatform;
use App\Domain\Pemasaran\Domain\KatalogIzinPemasaran;
use App\Domain\Pemasaran\Http\Controllers\AturanSkorProspekController;
use App\Domain\Pemasaran\Http\Controllers\DashboardGrowthController;
use App\Domain\Pemasaran\Http\Controllers\DemoPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\FormulirPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\HalamanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\ImporEksporProspekController;
use App\Domain\Pemasaran\Http\Controllers\KampanyeController;
use App\Domain\Pemasaran\Http\Controllers\KampanyeKonsolController;
use App\Domain\Pemasaran\Http\Controllers\KonsenPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\OtomasiPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\PengaturanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\ProspekController;
use App\Domain\Pemasaran\Http\Controllers\RedirectPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\ReferralController;
use App\Domain\Pemasaran\Http\Controllers\RingkasanPemasaranController;
use App\Domain\Pemasaran\Http\Controllers\SequenceEmailController;
use App\Domain\Pemasaran\Http\Controllers\TemplateEmailController;
use App\Domain\Pemasaran\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

// Konsol Growth & Marketing (MARKETING.md 4).
Route::middleware(['web', 'auth:platform'])
    ->prefix('admin-platform/pemasaran')
    ->name('pemasaran.')
    ->group(function (): void {
        Route::get('/', RingkasanPemasaranController::class)
            ->middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_LIHAT)
            ->name('ringkasan');

        // CRM prospek.
        Route::middleware('fitur.platform:'.KatalogFiturPlatform::CRM)
            ->prefix('prospek')
            ->name('prospek.')
            ->group(function (): void {
                // Aturan bobot skor.
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

                // Ekspor memindahkan data pribadi keluar sistem.
                Route::get('/ekspor/csv', [ImporEksporProspekController::class, 'ekspor'])
                    ->middleware([
                        'izin.platform:'.KatalogIzinPemasaran::PROSPEK_EKSPOR,
                        'throttle:ekspor',
                    ])
                    ->name('ekspor');
            });

        // Trial berbagi flag dengan CRM: perjalanannya adalah perjalanan prospek yang sama.
        Route::middleware('fitur.platform:'.KatalogFiturPlatform::CRM)
            ->prefix('trial')
            ->name('trial.')
            ->group(function (): void {
                Route::get('/', [TrialController::class, 'index'])
                    ->middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_LIHAT)
                    ->name('index');

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_KELOLA)->group(function (): void {
                    Route::post('/{trial}/perpanjang', [TrialController::class, 'perpanjang'])->name('perpanjang');
                    Route::post('/{trial}/status', [TrialController::class, 'ubahStatus'])->name('status');
                });
            });

        // Konsol demo produk: setelan, sesi, dan reset datasetnya (MARKETING.md 11).
        Route::middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_LIHAT)
            ->prefix('demo')->name('demo.')->group(function (): void {
                Route::get('/', [DemoPemasaranController::class, 'index'])->name('index');

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_KELOLA)->group(function (): void {
                    Route::post('/', [DemoPemasaranController::class, 'store'])->name('store');
                    Route::put('/{demo}', [DemoPemasaranController::class, 'update'])->name('update');
                    Route::post('/{demo}/reset', [DemoPemasaranController::class, 'reset'])->name('reset');
                });
            });

        // Kampanye berada di balik flag analitik: tanpa modulnya hidup, tidak ada tempat angkanya dibaca.
        Route::middleware([
            'izin.platform:'.KatalogIzinPemasaran::KAMPANYE_LIHAT,
            'fitur.platform:'.KatalogFiturPlatform::ANALITIK,
        ])->prefix('kampanye')->name('kampanye.')->group(function (): void {
            Route::get('/', [KampanyeController::class, 'index'])->name('index');
            Route::get('/{kampanye}', [KampanyeController::class, 'show'])->name('show');

            Route::middleware('izin.platform:'.KatalogIzinPemasaran::KAMPANYE_KELOLA)->group(function (): void {
                Route::post('/', [KampanyeController::class, 'store'])->name('store');
                Route::put('/{kampanye}', [KampanyeController::class, 'update'])->name('update');

                Route::post('/{kampanye}/biaya', [KampanyeKonsolController::class, 'simpanBiaya'])
                    ->name('biaya.simpan');
                Route::delete('/{kampanye}/biaya/{biaya}', [KampanyeKonsolController::class, 'hapusBiaya'])
                    ->name('biaya.hapus');
                Route::put('/{kampanye}/target', [KampanyeKonsolController::class, 'simpanTarget'])
                    ->name('target.simpan');
                Route::post('/{kampanye}/konten', [KampanyeKonsolController::class, 'simpanKonten'])
                    ->name('konten.simpan');
                Route::delete('/{kampanye}/konten/{konten}', [KampanyeKonsolController::class, 'hapusKonten'])
                    ->name('konten.hapus');
            });
        });

        // Landing page builder dan peta redirect.
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

                // Menerbitkan, menjadwalkan, dan mengembalikan mengubah isi situs publik.
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

                // Redirect mengubah alamat yang dilihat mesin pencari, jadi haknya sama dengan menerbitkan halaman.
                Route::middleware('izin.platform:'.KatalogIzinPemasaran::HALAMAN_TERBITKAN)
                    ->group(function (): void {
                        Route::post('/', [RedirectPemasaranController::class, 'store'])->name('store');
                        Route::put('/{redirect}', [RedirectPemasaranController::class, 'update'])->name('update');
                        Route::delete('/{redirect}', [RedirectPemasaranController::class, 'destroy'])
                            ->name('destroy');
                    });
            });
        });

        // Dashboard growth: satu layar untuk seluruh corong pemasaran.
        Route::prefix('growth')->name('growth.')->group(function (): void {
            Route::get('/', [DashboardGrowthController::class, 'index'])
                ->middleware('izin.platform:'.KatalogIzinPemasaran::ANALYTICS_LIHAT)
                ->name('index');

            Route::post('/alert/{alert}/selesai', [DashboardGrowthController::class, 'selesaikanAlert'])
                ->middleware('izin.platform:'.KatalogIzinPemasaran::PEMASARAN_KELOLA)
                ->name('alert.selesai');
        });

        // Program referral, kode pelanggan, dan imbalannya.
        Route::prefix('referral')->name('referral.')->group(function (): void {
            Route::get('/', [ReferralController::class, 'index'])
                ->middleware('izin.platform:'.KatalogIzinPemasaran::REFERRAL_LIHAT)
                ->name('index');

            Route::middleware('izin.platform:'.KatalogIzinPemasaran::REFERRAL_KELOLA)
                ->group(function (): void {
                    Route::post('/', [ReferralController::class, 'store'])->name('store');
                    Route::put('/{program}', [ReferralController::class, 'update'])->name('update');
                    Route::post('/{program}/kode', [ReferralController::class, 'terbitkanKode'])
                        ->name('kode');
                    Route::post('/reward/{reward}/proses', [ReferralController::class, 'prosesReward'])
                        ->name('reward.proses');
                    Route::post('/reward/{reward}/batalkan', [ReferralController::class, 'batalkanReward'])
                        ->name('reward.batalkan');
                });
        });

        // Otomasi pemasaran: Trigger → Condition → Delay → Action.
        Route::prefix('otomasi')->name('otomasi.')->group(function (): void {
            Route::middleware('izin.platform:'.KatalogIzinPemasaran::OTOMASI_LIHAT)->group(function (): void {
                Route::get('/', [OtomasiPemasaranController::class, 'index'])->name('index');
                Route::get('/{otomasi}', [OtomasiPemasaranController::class, 'show'])->name('show');
            });

            Route::middleware('izin.platform:'.KatalogIzinPemasaran::OTOMASI_KELOLA)->group(function (): void {
                Route::post('/', [OtomasiPemasaranController::class, 'store'])->name('store');
                Route::put('/{otomasi}', [OtomasiPemasaranController::class, 'update'])->name('update');
                Route::post('/{otomasi}/versi', [OtomasiPemasaranController::class, 'buatVersi'])
                    ->name('versi.store');
                Route::post('/{otomasi}/versi/{versi}/langkah', [
                    OtomasiPemasaranController::class, 'simpanLangkah',
                ])->name('langkah.store');
                Route::put('/{otomasi}/versi/{versi}/langkah/{langkah}', [
                    OtomasiPemasaranController::class, 'simpanLangkah',
                ])->name('langkah.update');
                Route::delete('/{otomasi}/versi/{versi}/langkah/{langkah}', [
                    OtomasiPemasaranController::class, 'hapusLangkah',
                ])->name('langkah.destroy');
            });

            // Mengaktifkan otomasi mulai mengirim pesan ke orang sungguhan, jadi haknya sendiri.
            Route::middleware('izin.platform:'.KatalogIzinPemasaran::OTOMASI_AKTIFKAN)->group(function (): void {
                Route::post('/{otomasi}/versi/{versi}/aktifkan', [
                    OtomasiPemasaranController::class, 'aktifkanVersi',
                ])->name('versi.aktifkan');
                Route::post('/{otomasi}/aktif', [OtomasiPemasaranController::class, 'ubahAktif'])
                    ->name('aktif');
            });
        });

        // Email pemasaran: template, sequence, dan consent.
        Route::prefix('email')->name('email.')->group(function (): void {
            Route::prefix('template')->name('template.')->group(function (): void {
                Route::get('/', [TemplateEmailController::class, 'index'])
                    ->middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_LIHAT)
                    ->name('index');

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_KELOLA)
                    ->group(function (): void {
                        Route::post('/', [TemplateEmailController::class, 'store'])->name('store');
                        Route::put('/{template}', [TemplateEmailController::class, 'update'])->name('update');
                        Route::delete('/{template}', [TemplateEmailController::class, 'destroy'])
                            ->name('destroy');
                    });
            });

            Route::prefix('sequence')->name('sequence.')->group(function (): void {
                Route::get('/', [SequenceEmailController::class, 'index'])
                    ->middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_LIHAT)
                    ->name('index');

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_KELOLA)
                    ->group(function (): void {
                        Route::post('/', [SequenceEmailController::class, 'store'])->name('store');
                        Route::put('/{sequence}', [SequenceEmailController::class, 'update'])->name('update');
                        Route::post('/{sequence}/langkah', [SequenceEmailController::class, 'simpanLangkah'])
                            ->name('langkah.store');
                        Route::put('/{sequence}/langkah/{langkah}', [
                            SequenceEmailController::class, 'simpanLangkah',
                        ])->name('langkah.update');
                        Route::delete('/{sequence}/langkah/{langkah}', [
                            SequenceEmailController::class, 'hapusLangkah',
                        ])->name('langkah.destroy');
                    });
            });

            Route::prefix('konsen')->name('konsen.')->group(function (): void {
                Route::get('/', [KonsenPemasaranController::class, 'index'])
                    ->middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_LIHAT)
                    ->name('index');

                Route::middleware('izin.platform:'.KatalogIzinPemasaran::EMAIL_KELOLA)
                    ->group(function (): void {
                        Route::post('/supresi', [KonsenPemasaranController::class, 'supresi'])
                            ->name('supresi');
                        Route::post('/permintaan', [KonsenPemasaranController::class, 'catatPermintaan'])
                            ->name('permintaan');
                    });

                // Memproses permintaan menghapus data orang sungguhan, jadi haknya sama dengan mengekspornya.
                Route::post('/permintaan/{permintaan}/proses', [
                    KonsenPemasaranController::class, 'prosesPermintaan',
                ])->middleware('izin.platform:'.KatalogIzinPemasaran::PROSPEK_EKSPOR)->name('permintaan.proses');
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
