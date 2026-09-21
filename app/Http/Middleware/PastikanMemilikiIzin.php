<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Izin\PemeriksaIzin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PastikanMemilikiIzin
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function handle(Request $request, Closure $next, string $kodeIzin): Response
    {
        // Izin peran adalah konsep tenant; admin platform tidak memilikinya dan
        // tidak boleh lolos hanya karena sedang masuk di guard lain.
        $pengguna = $request->user('web');
        abort_unless($pengguna, 401);
        abort_unless($this->izin->boleh((string) $pengguna->Id, $kodeIzin), 403);

        return $next($request);
    }
}
