<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Batas laju untuk endpoint yang mahal atau rawan disalahgunakan (24).
 *
 * Kunci limiter dipilih sadar: IP saja menyatukan seluruh tenant yang berada di
 * balik satu NAT kantor, sedangkan identitas saja membuka penolakan layanan
 * yang diarahkan ke satu korban. Karena itu yang dipakai adalah identitas yang
 * paling spesifik yang tersedia, dengan IP sebagai cadangan.
 */
final class BatasLajuServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($this->kunciKlienApi($request)));

        // Pelengkap batas per akun di LoginController: yang ini menahan satu
        // sumber yang mencoba banyak akun berbeda, yang tidak tersentuh batas
        // berkunci email.
        RateLimiter::for('masuk', fn (Request $request): Limit => Limit::perMinute(20)
            ->by((string) $request->ip()));

        // Satu permintaan ekspor membangkitkan pekerjaan yang dapat menyentuh
        // belasan query agregat, jadi batasnya jauh lebih ketat daripada halaman
        // biasa.
        RateLimiter::for('ekspor', fn (Request $request): Limit => Limit::perMinute(6)
            ->by($this->kunciPemesan($request)));

        // Halaman publik anonim: satu IP adalah satu-satunya identitas yang ada,
        // dan batasnya longgar karena satu kunjungan wajar membuka banyak
        // halaman berturut-turut.
        RateLimiter::for('publik', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->ip()));

        // Endpoint publik: keabsahannya baru terbukti setelah tanda tangan
        // diperiksa, sehingga banjir permintaan palsu harus berhenti lebih dulu
        // di sini.
        RateLimiter::for('webhook', fn (Request $request): Limit => Limit::perMinute(120)
            ->by((string) $request->ip()));
    }

    /**
     * Awalan kunci API mengidentifikasi pemanggil tanpa membocorkan kuncinya,
     * dan membuat satu tenant tidak dapat menghabiskan kuota tenant lain.
     */
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
