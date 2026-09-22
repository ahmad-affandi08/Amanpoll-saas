<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/**
 * Titik masuk tunggal untuk menulis EventPemasaran (MARKETING.md 23).
 *
 * Jenis yang tidak ada di katalog ditolak, bukan disimpan diam-diam: peristiwa
 * dengan nama salah ketik tidak akan pernah muncul di corong mana pun, dan
 * kesalahan itu baru ketahuan berbulan kemudian ketika angkanya tidak cocok.
 */
final class PerekamEventPemasaran
{
    /** @param array<string, mixed>|null $dataTambahan */
    public function catat(
        string $jenis,
        ?string $pengenalPengunjung = null,
        ?string $sesiPengunjungId = null,
        ?string $url = null,
        ?array $dataTambahan = null,
    ): EventPemasaran {
        if (! KatalogPeristiwaPemasaran::dikenal($jenis)) {
            throw new AturanBisnisDilanggar("Jenis peristiwa pemasaran {$jenis} tidak dikenal.");
        }

        return EventPemasaran::create([
            'PengenalPengunjung' => $pengenalPengunjung,
            'SesiPengunjungId' => $sesiPengunjungId,
            'Jenis' => $jenis,
            'Url' => $url,
            'DataTambahan' => $dataTambahan,
            'TerjadiPada' => now(),
        ]);
    }
}
