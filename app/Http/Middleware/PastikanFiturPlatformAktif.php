<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Pemasaran\Application\Services\PemeriksaFiturPlatform;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menutup rute milik modul yang flag-nya mati (MARKETING.md 31).
 *
 * Menutup, bukan menyembunyikan: menu yang hilang sementara rutenya masih
 * dilayani adalah feature flag yang hanya berlaku bagi orang yang tidak
 * mengetik URL sendiri.
 */
final class PastikanFiturPlatformAktif
{
    public function __construct(private readonly PemeriksaFiturPlatform $fitur) {}

    public function handle(Request $request, Closure $next, string $kode): Response
    {
        abort_unless($this->fitur->aktif($kode), 404);

        return $next($request);
    }
}
