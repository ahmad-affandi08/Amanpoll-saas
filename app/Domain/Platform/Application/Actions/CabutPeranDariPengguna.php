<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;

final class CabutPeranDariPengguna
{
    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    public function jalankan(PenggunaPeran $penggunaPeran): void
    {
        $organisasiId = (string) $penggunaPeran->OrganisasiId;
        $penggunaId = (string) $penggunaPeran->PenggunaId;

        $penggunaPeran->delete();

        $this->pemeriksaIzin->bersihkanCache($organisasiId, $penggunaId);
    }
}
