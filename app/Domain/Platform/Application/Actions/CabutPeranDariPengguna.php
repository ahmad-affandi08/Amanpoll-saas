<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Izin\LingkupAkses;
use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;

final class CabutPeranDariPengguna
{
    public function __construct(
        private readonly PemeriksaIzin $pemeriksaIzin,
        private readonly LingkupAkses $lingkupAkses,
        private readonly PenentuModeLapangan $penentuModeLapangan,
    ) {}

    public function jalankan(PenggunaPeran $penggunaPeran): void
    {
        $organisasiId = (string) $penggunaPeran->OrganisasiId;
        $penggunaId = (string) $penggunaPeran->PenggunaId;

        $penggunaPeran->delete();

        $this->pemeriksaIzin->bersihkanCache($organisasiId, $penggunaId);
        // Cakupan pengguna ikut berubah saat penugasan dicabut, bukan hanya izinnya.
        $this->lingkupAkses->bersihkanCache($organisasiId, $penggunaId);
        // Mode Lapangan juga dibaca dari peran yang dipegang (PRD 8.20).
        $this->penentuModeLapangan->bersihkanCache($organisasiId, $penggunaId);
    }
}
