<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Services\PemeriksaEntitlement;
use App\Shared\Domain\Exceptions\LanggananTidakMengizinkan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemblokiran tulis untuk tenant yang langganannya habis (22.05, Gate 22).
 *
 * Dipasang pada grup web dan api, bukan per rute, supaya tidak ada rute yang
 * dapat lupa dijaga.
 */
final class PastikanLanggananMengizinkanTulis
{
    /**
     * Metode yang tidak mengubah keadaan, jadi tetap diizinkan saat
     * kedaluwarsa.
     */
    private const METODE_BACA = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Rute yang harus tetap dapat ditulis walau langganan habis, karena justru
     * lewat sinilah tenant memulihkan langganannya.
     */
    private const RUTE_DIKECUALIKAN = [
        'logout',
        'langganan.tagihan.bayar',
    ];

    public function __construct(
        private readonly KonteksOrganisasi $konteks,
        private readonly PemeriksaEntitlement $entitlement,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->konteks->id() === null) {
            // Belum ada tenant pada permintaan ini (halaman masuk, webhook,
            // endpoint status). Tidak ada langganan yang bisa dinilai.
            return $next($request);
        }

        if (in_array($request->method(), self::METODE_BACA, true)) {
            return $next($request);
        }

        if ($this->dikecualikan($request)) {
            return $next($request);
        }

        $entitlement = $this->entitlement->sekarang();
        if ($entitlement->memberiAksesPenuh()) {
            return $next($request);
        }

        throw new LanggananTidakMengizinkan($entitlement->status->alasanTulisDitolak());
    }

    private function dikecualikan(Request $request): bool
    {
        $nama = $request->route()?->getName();

        return $nama !== null && in_array($nama, self::RUTE_DIKECUALIKAN, true);
    }
}
