<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Pemasaran\Application\Services\PerekamEventPemasaran;
use App\Domain\Pemasaran\Application\Services\PerekamKunjungan;
use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Merekam kunjungan halaman publik (MARKETING.md 14, 23). */
final class RekamKunjunganPemasaran
{
    public function __construct(
        private readonly PerekamKunjungan $perekam,
        private readonly PerekamEventPemasaran $event,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $respons = $next($request);

        $pengenal = $request->attributes->get('pengenalPengunjung');

        if (! is_string($pengenal) || ! $request->isMethod('GET') || $respons->getStatusCode() !== 200) {
            return $respons;
        }

        $sesi = $this->perekam->rekam($request, $pengenal);

        $this->event->catat(
            KatalogPeristiwaPemasaran::HALAMAN_DILIHAT,
            pengenalPengunjung: $pengenal,
            sesiPengunjungId: $sesi->Id,
            url: $request->fullUrl(),
        );

        return $respons;
    }
}
