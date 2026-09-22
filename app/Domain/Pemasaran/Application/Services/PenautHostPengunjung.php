<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Http\Middleware\TetapkanSesiPengunjung;

/** Menyusun tautan dari host publik ke host dashboard tanpa memutus identitas pengunjung (MARKETING.md 1.1, 14). */
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

    /** Cookie melintas sendiri hanya bila domain induknya dikonfigurasi. */
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
