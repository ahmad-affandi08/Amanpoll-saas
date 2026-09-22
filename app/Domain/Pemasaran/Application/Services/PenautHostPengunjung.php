<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Http\Middleware\TetapkanSesiPengunjung;

/**
 * Menyusun tautan dari host publik ke host dashboard tanpa memutus identitas
 * pengunjung (MARKETING.md 1.1, 14).
 *
 * Bila kedua host berbagi domain induk, cookie sudah cukup dan tautannya
 * dibiarkan bersih. Bila tidak — pengembangan lokal, staging yang hostnya
 * terpisah — pengenalnya dititipkan sekali lewat parameter, lalu middleware di
 * host tujuan segera memindahkannya ke cookie.
 */
final class PenautHostPengunjung
{
    public function __construct(private readonly PetaHost $host) {}

    public function tautan(string $url, ?string $pengenalPengunjung): string
    {
        if ($pengenalPengunjung === null || $this->cookieSudahLintasHost()) {
            return $url;
        }

        $pemisah = str_contains($url, '?') ? '&' : '?';

        return $url.$pemisah.TetapkanSesiPengunjung::PARAMETER_SERAH_TERIMA.'='.urlencode($pengenalPengunjung);
    }

    /**
     * Cookie melintas sendiri hanya bila domain induknya dikonfigurasi dan
     * kedua host benar-benar berada di bawahnya.
     */
    private function cookieSudahLintasHost(): bool
    {
        $induk = $this->host->cookieInduk();

        if ($induk === null) {
            return false;
        }

        $induk = ltrim($induk, '.');

        return str_ends_with($this->host->dashboard(), $induk)
            && str_ends_with((string) $this->host->publik(), $induk);
    }
}
