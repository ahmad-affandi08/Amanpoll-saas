<?php

declare(strict_types=1);

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Http\Controllers\FormulirPublikController;
use App\Domain\Pemasaran\Http\Controllers\HalamanPublikController;
use App\Domain\Pemasaran\Http\Controllers\RobotsController;
use App\Http\Middleware\AlihkanKeHostKanonik;
use App\Http\Middleware\CacheResponsPublik;
use App\Http\Middleware\RekamKunjunganPemasaran;
use App\Http\Middleware\TandaiTidakTerindeks;
use App\Http\Middleware\TerapkanRedirectPemasaran;
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
            // Pengenal pengunjung sudah ditetapkan grup `web`, jadi tidak
            // dipasang ulang di sini: dua kali jalan akan melahirkan dua ULID
            // berbeda pada kunjungan pertama yang sama.
            'web',
            AlihkanKeHostKanonik::class,
            // Setelah pengenal pengunjung ada, supaya cookienya tetap terkirim
            // bersama respons pengalihan dan perjalanan pengunjung tidak putus
            // tepat di alamat lama yang sedang dipindahkan.
            TerapkanRedirectPemasaran::class,
            RekamKunjunganPemasaran::class,
        ])
        ->name('publik.')
        ->group(function (): void {
            Route::get('/', [HalamanPublikController::class, 'beranda'])
                ->middleware('throttle:publik')
                ->name('beranda');

            // Tidak di-throttle: perayap mengambilnya rutin dan memblokirnya
            // justru merugikan indeks yang ingin kita bangun. Isinya sama bagi
            // semua orang, jadi cukup dihitung sekali per interval.
            Route::middleware(CacheResponsPublik::class.':3600')->group(function (): void {
                Route::get('/robots.txt', [RobotsController::class, 'robotsPublik'])->name('robots');
                Route::get('/sitemap.xml', [RobotsController::class, 'sitemap'])->name('sitemap');
            });

            Route::get('/pratinjau/{halaman}/{versi}', [HalamanPublikController::class, 'pratinjau'])
                ->middleware(['signed', 'throttle:publik', TandaiTidakTerindeks::class])
                ->name('pratinjau');

            Route::post('/formulir/{formulir}', FormulirPublikController::class)
                ->middleware('throttle:formulir')
                ->name('formulir');

            /*
             * Penampung terakhir: seluruh halaman pemasaran dilayani dari satu
             * rute, karena alamatnya ditentukan data dan bukan kode. Didaftarkan
             * paling akhir supaya rute bernama di atas tetap menang, dan
             * polanya sengaja menerima apa saja agar redirect untuk alamat lama
             * — termasuk yang berakhiran `.html` — tetap melewati middleware
             * grup ini alih-alih berhenti di 404 tanpa rute.
             */
            Route::get('/{jalur}', [HalamanPublikController::class, 'tampil'])
                ->where('jalur', '.*')
                ->middleware('throttle:publik')
                ->name('halaman');
        });
}

// Host non-publik tetap melayani robots.txt, dan isinya melarang seluruh
// perayapan (MARKETING.md 1.2).
Route::domain($host->dashboard())
    ->middleware('web')
    ->get('/robots.txt', [RobotsController::class, 'robotsTertutup'])
    ->name('dashboard.robots');
