<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Izin\PemeriksaIzinPlatform;
use App\Shared\Domain\Exceptions\AksesDitolak;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Gerbang izin konsol platform (MARKETING.md 26). */
final class PastikanIzinPlatform
{
    public function __construct(private readonly PemeriksaIzinPlatform $izin) {}

    public function handle(Request $request, Closure $next, string ...$kodeIzin): Response
    {
        $admin = $request->user('platform');

        foreach ($kodeIzin as $kode) {
            if ($this->izin->boleh($admin, $kode)) {
                return $next($request);
            }
        }

        throw new AksesDitolak('Anda tidak memiliki izin untuk membuka halaman ini.');
    }
}
