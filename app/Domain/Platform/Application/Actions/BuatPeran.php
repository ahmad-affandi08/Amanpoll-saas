<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Core\Organisasi\KonteksOrganisasi;
use App\Domain\Platform\Application\DTO\PeranData;
use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

final class BuatPeran
{
    public function __construct(
        private readonly PeranRepository $peranRepository,
        private readonly KonteksOrganisasi $konteks,
    ) {}

    public function jalankan(PeranData $data): Peran
    {
        $peran = new Peran([
            'OrganisasiId' => $this->konteks->wajibId(),
            'Kode' => $data->Kode,
            'Nama' => $data->Nama,
            'Keterangan' => $data->Keterangan,
        ]);

        return $this->peranRepository->simpan($peran);
    }
}
