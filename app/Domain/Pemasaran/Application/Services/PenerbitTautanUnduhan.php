<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Core\Host\PetaHost;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\PengirimanFormulir;
use Illuminate\Support\Facades\URL;

/**
 * Tautan unduhan lead magnet. Kuncinya adalah satu pengiriman formulir yang
 * benar-benar ada: tanpa mengisi formulir tidak ada yang dapat ditandatangani.
 */
final class PenerbitTautanUnduhan
{
    /** Cukup untuk mengunduh, terlalu pendek untuk beredar sebagai tautan bebas. */
    private const UMUR_MENIT = 60;

    public function __construct(private readonly PetaHost $host) {}

    public function untuk(PengirimanFormulir $pengiriman): ?string
    {
        if (! $this->host->situsPublikAktif()) {
            return null;
        }

        $formulir = $pengiriman->formulir;

        if ($formulir === null || ! $formulir->punyaBerkas()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'publik.unduhan',
            now()->addMinutes(self::UMUR_MENIT),
            ['pengiriman' => $pengiriman->Id],
        );
    }
}
