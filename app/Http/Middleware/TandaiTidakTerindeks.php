<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menandai satu rute sebagai tidak boleh diindeks, apa pun hostnya
 * (MARKETING.md 8).
 *
 * Berbeda dari TandaiHostTidakTerindeks, yang menutup seluruh host non-publik.
 * Yang ini untuk halaman yang memang berada di host publik tetapi tidak boleh
 * masuk indeks — pratinjau draf, yang isinya belum disetujui siapa pun dan
 * tidak pernah dimaksudkan untuk dibaca umum.
 */
final class TandaiTidakTerindeks
{
    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $respons->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $respons;
    }
}
