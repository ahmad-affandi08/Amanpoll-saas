<?php

declare(strict_types=1);

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Http\Controllers\BerhentiLanggananController;
use App\Domain\Pemasaran\Http\Controllers\DemoPublikController;
use App\Domain\Pemasaran\Http\Controllers\FormulirPublikController;
use App\Domain\Pemasaran\Http\Controllers\HalamanPublikController;
use App\Domain\Pemasaran\Http\Controllers\KlikReferralController;
use App\Domain\Pemasaran\Http\Controllers\RobotsController;
use App\Http\Middleware\AlihkanKeHostKanonik;
use App\Http\Middleware\CacheResponsPublik;
use App\Http\Middleware\RekamKunjunganPemasaran;
use App\Http\Middleware\TandaiTidakTerindeks;
use App\Http\Middleware\TerapkanRedirectPemasaran;
use Illuminate\Support\Facades\Route;

// Situs publik (MARKETING.md 34.1).
$host = app(PetaHost::class);

if ($host->situsPublikAktif()) {
    // Bentuk non-kanonik hanya dilayani untuk dialihkan.
    Route::domain((string) $host->publikNonKanonik())
        ->middleware(AlihkanKeHostKanonik::class)
        ->any('/{jalur?}', fn () => abort(404))
        ->where('jalur', '.*')
        ->name('publik.kanonik');

    Route::domain((string) $host->publikKanonik())
        ->middleware([
            // Pengenal pengunjung sudah ditetapkan grup `web`, jadi tidak dipasang ulang di sini.
            'web',
            AlihkanKeHostKanonik::class,
            // Setelah pengenal pengunjung ada.
            TerapkanRedirectPemasaran::class,
            RekamKunjunganPemasaran::class,
        ])
        ->name('publik.')
        ->group(function (): void {
            Route::get('/', [HalamanPublikController::class, 'beranda'])
                ->middleware('throttle:publik')
                ->name('beranda');

            // Tidak di-throttle: perayap mengambilnya rutin.
            Route::middleware(CacheResponsPublik::class.':3600')->group(function (): void {
                Route::get('/robots.txt', [RobotsController::class, 'robotsPublik'])->name('robots');
                Route::get('/sitemap.xml', [RobotsController::class, 'sitemap'])->name('sitemap');
            });

            Route::get('/pratinjau/{halaman}/{versi}', [HalamanPublikController::class, 'pratinjau'])
                ->middleware(['signed', 'throttle:publik', TandaiTidakTerindeks::class])
                ->name('pratinjau');

            // Tanpa kedaluwarsa: email lama tetap harus dapat dipakai berhenti berlangganan.
            Route::get('/berhenti-langganan/{pengiriman}', BerhentiLanggananController::class)
                ->middleware(['signed', 'throttle:publik', TandaiTidakTerindeks::class])
                ->name('berhenti-langganan');

            // Tautan referral: menukar kode dengan satu klik tercatat, lalu mengalihkan.
            Route::get('/r/{kode}', KlikReferralController::class)
                ->where('kode', '[A-Za-z0-9]+')
                ->middleware(['throttle:publik', TandaiTidakTerindeks::class])
                ->name('referral');

            Route::post('/formulir/{formulir}', FormulirPublikController::class)
                ->middleware('throttle:formulir')
                ->name('formulir');

            // Sesi demo: mulai, catat peristiwa, selesaikan (MARKETING.md 11).
            Route::middleware(['throttle:formulir', TandaiTidakTerindeks::class])
                ->prefix('demo')->name('demo.')->group(function (): void {
                    Route::post('/{demo:Kode}/mulai', [DemoPublikController::class, 'mulai'])->name('mulai');
                    Route::post('/sesi/{sesi}/event', [DemoPublikController::class, 'catat'])->name('event');
                    Route::post('/sesi/{sesi}/selesai', [DemoPublikController::class, 'selesai'])->name('selesai');
                });

            // Penampung terakhir: seluruh halaman pemasaran dilayani dari satu rute.
            Route::get('/{jalur}', [HalamanPublikController::class, 'tampil'])
                ->where('jalur', '.*')
                ->middleware('throttle:publik')
                ->name('halaman');
        });
}

// Host non-publik tetap melayani robots.txt, dan isinya melarang seluruh perayapan (MARKETING.md 1.2).
Route::domain($host->dashboard())
    ->middleware('web')
    ->get('/robots.txt', [RobotsController::class, 'robotsTertutup'])
    ->name('dashboard.robots');
