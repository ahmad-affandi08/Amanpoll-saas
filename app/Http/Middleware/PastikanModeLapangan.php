<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi rute `/lapangan` bagi pengguna yang memang memakai Mode Lapangan (PRD 8.20).
 *
 * Tanpa parameter, setiap mode diterima (Teknisi maupun Pelapor). Dengan
 * parameter, hanya mode yang disebut: `mode.lapangan:Teknisi` untuk layar
 * teknisi, `mode.lapangan:Pelapor,Teknisi` untuk layar pelapor karena teknisi
 * juga boleh melapor lewat aksi cepat.
 *
 * Kunjungan halaman yang tidak cocok dialihkan ke beranda yang sesuai --
 * beranda Mode Lapangan untuk mode lain, dasbor untuk pengguna meja -- supaya
 * tautan lama tidak berakhir di halaman galat. Permintaan lainnya ditolak 403.
 * Ini hanya pagar tampilan; data tetap dijaga policy domain pemiliknya.
 */
final class PastikanModeLapangan
{
    public function __construct(private readonly PenentuModeLapangan $penentuModeLapangan) {}

    public function handle(Request $request, Closure $next, string ...$modeDiizinkan): Response
    {
        $pengguna = $request->user('web');

        if ($pengguna === null) {
            abort(401);
        }

        $mode = $this->penentuModeLapangan->mode($pengguna);

        if ($mode !== null && ($modeDiizinkan === [] || in_array($mode->value, $modeDiizinkan, true))) {
            return $next($request);
        }

        if ($request->isMethod('GET') && ($request->header('X-Inertia') !== null || ! $request->expectsJson())) {
            return $mode === null
                ? redirect()->route('dashboard')
                : redirect()->route('lapangan.beranda');
        }

        abort(403, 'Layar ini hanya untuk pengguna Mode Lapangan yang sesuai.');
    }
}
