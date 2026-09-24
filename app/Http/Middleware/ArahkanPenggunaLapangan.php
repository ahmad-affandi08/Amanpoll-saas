<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Host\PetaHost;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengalihkan pengguna lapangan dari halaman dasbor web ke Mode Lapangan (PRD 8.20).
 *
 * Dipasang pada grup `web`, sehingga berlaku untuk setiap rute tanpa ada yang
 * lupa dijaga. Pengalihan dikerjakan di server, bukan dengan menyembunyikan
 * menu: pengguna lapangan murni yang mengetik URL dasbor tetap dibawa kembali.
 *
 * Aturannya, sebuah permintaan dialihkan ke `/lapangan` hanya bila SELURUH hal ini benar:
 *
 * 1. Ada pengguna tenant yang masuk (guard `web`).
 * 2. Rutenya milik host dasbor. Situs publik dan portal partner tidak disentuh.
 * 3. Metodenya GET atau HEAD. Permintaan tulis (POST/PUT/DELETE) tidak pernah
 *    dialihkan: layar Mode Lapangan memanggil endpoint domain yang sama dengan
 *    dasbor, dan otorisasinya tetap dijaga policy masing-masing.
 * 4. Permintaannya adalah kunjungan halaman: kunjungan Inertia (header
 *    `X-Inertia`), atau navigasi peramban biasa yang tidak meminta JSON.
 *    Permintaan data (`Accept: application/json` / XHR non-Inertia, seperti
 *    `@/lib/http`) dibiarkan lewat: ringkasan notifikasi, pencarian global,
 *    katalog izin, riwayat aset, lampiran dan komentar, dan sejenisnya.
 * 5. Jalurnya tidak termasuk `JALUR_BEBAS`: Mode Lapangan sendiri, API
 *    sinkronisasi offline, resolver label QR aset (`aset/pindai/*`, yang
 *    sendiri mengarahkan ke layar Mode Lapangan), unduhan dan thumbnail berkas
 *    (dibuka sebagai navigasi peramban atau `<img>`),
 *    pencarian global, ringkasan notifikasi, logout, robots.txt, dan konsol
 *    platform yang memakai guard terpisah.
 *
 * Lalu siapa yang dialihkan:
 *
 * - Pengguna **lapangan murni** (seluruh perannya bertanda Tampilan Lapangan):
 *   setiap halaman dasbor.
 * - Pengguna **campuran** yang di perangkat ini memilih Mode Lapangan (cookie
 *   `COOKIE_TAMPILAN` bernilai `lapangan`, diisi `POST /lapangan/tampilan`):
 *   hanya beranda dasbor (`/`), supaya login dan tombol "Dashboard" membawanya
 *   ke Mode Lapangan. Halaman dasbor lain tetap boleh ia buka.
 * - Pengguna meja tidak pernah dialihkan.
 */
final class ArahkanPenggunaLapangan
{
    /** Cookie pilihan tampilan pengguna campuran; diingat per perangkat. */
    public const COOKIE_TAMPILAN = 'amanpoll_tampilan';

    public const TAMPILAN_LAPANGAN = 'lapangan';

    public const TAMPILAN_DASBOR = 'dasbor';

    /**
     * Jalur host dasbor yang tetap terbuka bagi pengguna lapangan murni,
     * dalam pola `Request::is()`.
     *
     * @var list<string>
     */
    public const JALUR_BEBAS = [
        'lapangan',
        'lapangan/*',
        'offline/*',
        'aset/pindai/*',
        'kolaborasi/berkas/*/unduh',
        // Thumbnail foto (grid foto tiket, foto aset) dimuat `<img>` tanpa header JSON (PRD 8.4, 11.1).
        'kolaborasi/berkas/*/thumbnail',
        'cari',
        'notifikasi/ringkasan',
        'logout',
        'robots.txt',
        'admin-platform',
        'admin-platform/*',
    ];

    public function __construct(
        private readonly PenentuModeLapangan $penentuModeLapangan,
        private readonly PetaHost $petaHost,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $pengguna = $request->user('web');

        if ($pengguna === null || ! $this->kunjunganHalamanDasbor($request)) {
            return $next($request);
        }

        if ($this->penentuModeLapangan->lapanganMurni($pengguna)) {
            return redirect()->route('lapangan.beranda');
        }

        if ($request->routeIs('dashboard')
            && $request->cookie(self::COOKIE_TAMPILAN) === self::TAMPILAN_LAPANGAN
            && $this->penentuModeLapangan->bisaBeralih($pengguna)) {
            return redirect()->route('lapangan.beranda');
        }

        return $next($request);
    }

    private function kunjunganHalamanDasbor(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if ($request->route()?->getDomain() !== $this->petaHost->dashboard()) {
            return false;
        }

        if ($request->is(...self::JALUR_BEBAS)) {
            return false;
        }

        return $request->header('X-Inertia') !== null || ! $request->expectsJson();
    }
}
