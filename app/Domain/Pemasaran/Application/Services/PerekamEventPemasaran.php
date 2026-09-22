<?php

declare(strict_types=1);

namespace App\Domain\Pemasaran\Application\Services;

use App\Domain\Pemasaran\Domain\KatalogPeristiwaPemasaran;
use App\Domain\Pemasaran\Infrastructure\Persistence\Models\EventPemasaran;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

/** Titik masuk tunggal untuk menulis EventPemasaran (MARKETING.md 23). */
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
