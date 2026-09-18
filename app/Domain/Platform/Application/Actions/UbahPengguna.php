<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class UbahPengguna
{
    public function __construct(private readonly PenggunaRepository $penggunaRepository) {}

    public function jalankan(Pengguna $pengguna, PenggunaData $data): Pengguna
    {
        $pengguna->fill([
            'UnitOrganisasiId' => $data->UnitOrganisasiId,
            'Nama' => $data->Nama,
            'Email' => $data->Email,
            'Telepon' => $data->Telepon,
            'NomorPegawai' => $data->NomorPegawai,
            'Jabatan' => $data->Jabatan,
            'JenisPengguna' => $data->JenisPengguna,
        ]);

        if (!empty($data->KataSandi)) {
            $pengguna->KataSandi = $data->KataSandi;
        }

        return $this->penggunaRepository->simpan($pengguna);
    }
}
