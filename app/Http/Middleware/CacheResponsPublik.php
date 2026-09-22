<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache respons untuk isi publik yang sama bagi semua orang (MARKETING.md 1).
 *
 * Dipakai `robots.txt` dan `sitemap.xml`: keduanya byte-identik bagi setiap
 * pengunjung, tidak membawa sesi, dan diambil perayap berulang kali.
 *
 * Halaman HTML sengaja belum ikut. Badan halaman Inertia memuat token CSRF
 * milik satu sesi, sehingga menyajikannya ulang ke orang lain akan
 * membagikan token yang salah. Saat halaman pemasaran yang sesungguhnya lahir
 * di FASE 32, yang di-cache adalah isi halaman terbitannya — bukan respons
 * HTTP-nya — sehingga persoalan token tidak muncul sama sekali.
 *
 * Header `Set-Cookie` tidak pernah ikut tersimpan. Satu saja yang terbawa akan
 * membagikan pengenal pengunjung yang sama ke semua orang dan membuat seluruh
 * attribution salah.
 */
final class CacheResponsPublik
{
    public function __construct(private readonly Cache $cache) {}

    public function handle(Request $request, Closure $next, string $detik = '300'): Response
    {
        if (! $request->isMethod('GET') || $request->user() !== null) {
            return $next($request);
        }

        $kunci = $this->kunci($request);

        /** @var array{isi: string, tipe: string}|null $tersimpan */
        $tersimpan = $this->cache->get($kunci);

        if ($tersimpan !== null) {
            return response($tersimpan['isi'], 200, [
                'Content-Type' => $tersimpan['tipe'],
                'Cache-Control' => 'public, max-age='.$detik,
                'X-Cache-Amanpoll' => 'hit',
            ]);
        }

        $respons = $next($request);
        $isi = $respons->getContent();

        if ($respons->getStatusCode() === 200 && is_string($isi)) {
            $this->cache->put($kunci, [
                'isi' => $isi,
                'tipe' => (string) $respons->headers->get('Content-Type', 'text/plain'),
            ], (int) $detik);

            $respons->headers->set('Cache-Control', 'public, max-age='.$detik);
            $respons->headers->set('X-Cache-Amanpoll', 'miss');
        }

        return $respons;
    }

    private function kunci(Request $request): string
    {
        return 'publik:respons:'.sha1($request->getSchemeAndHttpHost().$request->getRequestUri());
    }
}
