<?php

declare(strict_types=1);

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Http\Controllers\BerandaPublikController;
use App\Domain\Pemasaran\Http\Controllers\RobotsController;
use App\Http\Middleware\AlihkanKeHostKanonik;
use App\Http\Middleware\CacheResponsPublik;
use App\Http\Middleware\RekamKunjunganPemasaran;
use App\Http\Middleware\TetapkanSesiPengunjung;
use Illuminate\Support\Facades\Route;

/*
 * Situs publik (MARKETING.md 34.1).
 *
 * Seluruh rute di sini anonim: tanpa `auth`, tanpa `organisasi`, dan tidak
 * pernah memuat data tenant. Tombol "Masuk" dan "Coba Gratis" mengarah ke host
 * dashboard, sehingga sesi dan cookie yang terbentuk sudah benar sejak awal.
 */
$host = app(PetaHost::class);

if ($host->situsPublikAktif()) {
    // Bentuk non-kanonik hanya dilayani untuk dialihkan. Tanpa grup ini ia
    // menjawab 404 — host tanpa rute, bukan host yang salah bentuk.
    Route::domain((string) $host->publikNonKanonik())
        ->middleware(AlihkanKeHostKanonik::class)
        ->any('/{jalur?}', fn () => abort(404))
        ->where('jalur', '.*')
        ->name('publik.kanonik');

    Route::domain((string) $host->publikKanonik())
        ->middleware([
            'web',
            AlihkanKeHostKanonik::class,
            TetapkanSesiPengunjung::class,
            RekamKunjunganPemasaran::class,
        ])
        ->name('publik.')
        ->group(function (): void {
            Route::get('/', BerandaPublikController::class)
                ->middleware('throttle:publik')
                ->name('beranda');

            // Tidak di-throttle: perayap mengambilnya rutin dan memblokirnya
            // justru merugikan indeks yang ingin kita bangun. Isinya sama bagi
            // semua orang, jadi cukup dihitung sekali per interval.
            Route::middleware(CacheResponsPublik::class.':3600')->group(function (): void {
                Route::get('/robots.txt', [RobotsController::class, 'robotsPublik'])->name('robots');
                Route::get('/sitemap.xml', [RobotsController::class, 'sitemap'])->name('sitemap');
            });
        });
}

// Host non-publik tetap melayani robots.txt, dan isinya melarang seluruh
// perayapan (MARKETING.md 1.2).
Route::domain($host->dashboard())
    ->middleware('web')
    ->get('/robots.txt', [RobotsController::class, 'robotsTertutup'])
    ->name('dashboard.robots');
