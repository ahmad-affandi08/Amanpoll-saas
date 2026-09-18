<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\PenggunaPeran;
use App\Shared\Domain\Exceptions\KonflikData;

final class TetapkanPeranKePengguna
{
    public function __construct(private readonly PemeriksaIzin $pemeriksaIzin) {}

    public function jalankan(
        Pengguna $pengguna,
        Peran $peran,
        ?string $unitOrganisasiId = null,
        ?string $lokasiId = null,
    ): PenggunaPeran {
        $sudahAda = PenggunaPeran::query()
            ->where('PenggunaId', $pengguna->Id)
            ->where('PeranId', $peran->Id)
            ->when(
                $unitOrganisasiId === null,
                fn ($q) => $q->whereNull('UnitOrganisasiId'),
                fn ($q) => $q->where('UnitOrganisasiId', $unitOrganisasiId),
            )
            ->when(
                $lokasiId === null,
                fn ($q) => $q->whereNull('LokasiId'),
                fn ($q) => $q->where('LokasiId', $lokasiId),
            )
            ->exists();

        if ($sudahAda) {
            throw new KonflikData('Peran ini sudah ditetapkan ke pengguna dengan cakupan yang sama.');
        }

        $penggunaPeran = PenggunaPeran::create([
            'PenggunaId' => $pengguna->Id,
            'PeranId' => $peran->Id,
            'UnitOrganisasiId' => $unitOrganisasiId,
            'LokasiId' => $lokasiId,
        ]);

        $this->pemeriksaIzin->bersihkanCache((string) $peran->OrganisasiId, (string) $pengguna->Id);

        return $penggunaPeran;
    }
}
