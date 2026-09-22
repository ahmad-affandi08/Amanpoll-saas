<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Host\PetaHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Satu halaman publik hanya boleh punya satu URL (MARKETING.md 1.2).
 *
 * Bentuk apex dan bentuk www adalah dua URL berbeda bagi mesin pencari:
 * peringkat satu halaman terbelah, dan attribution pemasaran menghitung satu
 * kunjungan sebagai dua sumber.
 */
final class AlihkanKeHostKanonik
{
    public function __construct(private readonly PetaHost $host) {}

    public function handle(Request $request, Closure $next): Response
    {
        $kanonik = $this->host->publikKanonik();

        if ($kanonik === null || $request->getHost() === $kanonik || ! $request->isMethod('GET')) {
            return $next($request);
        }

        $tujuan = $request->getSchemeAndHttpHost();
        $tujuan = str_replace($request->getHost(), $kanonik, $tujuan).$request->getRequestUri();

        return redirect()->away($tujuan, 301);
    }
}
