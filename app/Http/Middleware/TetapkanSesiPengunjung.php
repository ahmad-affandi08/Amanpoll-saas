<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Host\PetaHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Identitas pengunjung anonim yang bertahan lintas host (MARKETING.md 1.1). */
final class TetapkanSesiPengunjung
{
    public const NAMA_COOKIE = 'amanpoll_pengunjung';

    private const UMUR_MENIT = 60 * 24 * 365;

    public function __construct(private readonly PetaHost $host) {}

    /** Parameter serah terima antar host. */
    public const PARAMETER_SERAH_TERIMA = '_p';

    public function handle(Request $request, Closure $next): Response
    {
        $pengenal = $this->pengenalSah($request->cookie(self::NAMA_COOKIE));

        // Serah terima hanya diterima bila belum ada cookie.
        $pengenal ??= $this->pengenalSah($request->query(self::PARAMETER_SERAH_TERIMA));

        $baru = $pengenal === null;
        $pengenal ??= (string) Str::ulid();

        $request->attributes->set('pengenalPengunjung', $pengenal);

        $respons = $next($request);

        // Pengenal hasil serah terima ikut dituliskan ke cookie, supaya parameter URL-nya cukup dipakai sekali.
        if ($baru || $request->cookie(self::NAMA_COOKIE) === null) {
            $respons->headers->setCookie(Cookie::make(
                name: self::NAMA_COOKIE,
                value: $pengenal,
                minutes: self::UMUR_MENIT,
                domain: $this->host->cookieInduk(),
                httpOnly: true,
                sameSite: 'lax',
            ));
        }

        return $respons;
    }

    /** Nilai cookie datang dari klien, jadi bentuknya diperiksa sebelum dipakai. */
    private function pengenalSah(mixed $nilai): ?string
    {
        return is_string($nilai) && Str::isUlid($nilai) ? $nilai : null;
    }
}
