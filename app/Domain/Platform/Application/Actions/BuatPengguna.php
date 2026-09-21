<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Langganan\Application\Services\PenjagaBatasLangganan;
use App\Domain\Langganan\Domain\KatalogFitur;
use App\Domain\Platform\Application\DTO\PenggunaData;
use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class BuatPengguna
{
    public function __construct(
        private readonly PenggunaRepository $penggunaRepository,
        private readonly KonteksOrganisasi $konteks,
        private readonly PenjagaBatasLangganan $penjagaBatas,
    ) {}

    public function jalankan(PenggunaData $data): Pengguna
    {
        // Kuota pengguna diperiksa di sini supaya undangan lewat API maupun
        // lewat UI sama-sama terjaga (22.05, Gate 22).
        $this->penjagaBatas->pastikanMasihMuat(KatalogFitur::BATAS_PENGGUNA);

        $pengguna = new Pengguna([
            'OrganisasiId' => $this->konteks->wajibId(),
            'UnitOrganisasiId' => $data->UnitOrganisasiId,
            'Nama' => $data->Nama,
            'Email' => $data->Email,
            'Telepon' => $data->Telepon,
            'KataSandi' => $data->KataSandi,
            'NomorPegawai' => $data->NomorPegawai,
            'Jabatan' => $data->Jabatan,
            'JenisPengguna' => $data->JenisPengguna,
            'Status' => 'Aktif',
        ]);

        return $this->penggunaRepository->simpan($pengguna);
    }
}
