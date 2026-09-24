<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\DTO\PeranData;
use App\Domain\Platform\Application\Services\PenentuModeLapangan;
use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

final class UbahPeran
{
    public function __construct(
        private readonly PeranRepository $peranRepository,
        private readonly PenentuModeLapangan $penentuModeLapangan,
    ) {}

    public function jalankan(Peran $peran, PeranData $data): Peran
    {
        $peran->fill([
            'Kode' => $data->Kode,
            'Nama' => $data->Nama,
            'Keterangan' => $data->Keterangan,
            'TampilanLapangan' => $data->TampilanLapangan,
        ]);

        $penandaBerubah = $peran->isDirty('TampilanLapangan');
        $tersimpan = $this->peranRepository->simpan($peran);

        // Pemegang peran ini bisa berpindah antara dasbor dan Mode Lapangan.
        if ($penandaBerubah) {
            $this->penentuModeLapangan->bersihkanCachePeran((string) $tersimpan->OrganisasiId, (string) $tersimpan->Id);
        }

        return $tersimpan;
    }
}
