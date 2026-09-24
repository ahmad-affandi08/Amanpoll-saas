<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/** Batas laju untuk endpoint yang mahal atau rawan disalahgunakan (24). */
final class BatasLajuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($this->kunciKlienApi($request)));

        // Pelengkap batas per akun di LoginController.
        RateLimiter::for('masuk', fn (Request $request): Limit => Limit::perMinute(20)
            ->by((string) $request->ip()));

        // Satu permintaan ekspor membangkitkan pekerjaan yang dapat menyentuh belasan query agregat.
        RateLimiter::for('ekspor', fn (Request $request): Limit => Limit::perMinute(6)
            ->by($this->kunciPemesan($request)));

        // Halaman publik anonim: satu IP adalah satu-satunya identitas yang ada, dan batasnya longgar.
        RateLimiter::for('publik', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->ip()));

        // Endpoint publik: keabsahannya baru terbukti setelah tanda tangan diperiksa.
        RateLimiter::for('webhook', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->ip()));

        // Satu pendaftaran trial melahirkan organisasi, pengguna, dan langganan sekaligus.
        RateLimiter::for('daftar', fn (Request $request): Limit => Limit::perMinute(3)
            ->by((string) $request->ip()));

        // Pencarian global: tiap ketikan (setelah jeda 250 ms) menjalankan LIKE di enam modul.
        // 120 per menit tetap longgar untuk orang yang mengetik, tetapi memutus skrip yang membanjiri.
        RateLimiter::for('pencarian', fn (Request $request): Limit => Limit::perMinute(120)
            ->by($this->kunciPemesan($request)));

        // Pengiriman formulir pemasaran.
        RateLimiter::for('formulir', fn (Request $request): Limit => Limit::perMinute(5)
            ->by((string) $request->ip()));
    }

    /** Awalan kunci API mengidentifikasi pemanggil tanpa membocorkan kuncinya. */
    private function kunciKlienApi(Request $request): string
    {
        $kunci = (string) $request->bearerToken();
        $awalan = explode('.', $kunci, 2)[0];

        return $awalan === '' ? 'ip:'.(string) $request->ip() : 'kunci:'.$awalan;
    }

    private function kunciPemesan(Request $request): string
    {
        $pengguna = $request->user('web');

        return $pengguna === null ? 'ip:'.(string) $request->ip() : 'pengguna:'.(string) $pengguna->Id;
    }
}
