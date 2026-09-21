<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang fitur per rute (22.05).
 *
 * Dipasang pada grup rute modul berbayar, dan berlaku untuk baca maupun tulis:
 * modul yang tidak dibeli tidak seharusnya dapat dibaca lewat API hanya karena
 * menunya disembunyikan di UI (Gate 22).
 */
final class PastikanFiturPaketAktif
{
    public function __construct(private readonly PemeriksaEntitlement $entitlement) {}

    public function handle(Request $request, Closure $next, string $kodeFitur): Response
    {
        $definisi = KatalogFitur::ambil($kodeFitur);

        if ($this->entitlement->bolehFitur($kodeFitur)) {
            return $next($request);
        }

        throw new LanggananTidakMengizinkan(
            "{$definisi->nama} tidak termasuk dalam paket langganan organisasi Anda. "
            .'Naikkan paket untuk mengaktifkan modul ini.',
        );
    }
}
