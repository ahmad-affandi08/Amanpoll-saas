<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Application\DTO\PeranData;
use App\Domain\Platform\Domain\Repositories\PeranRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

final class UbahPeran
{
    public function __construct(private readonly PeranRepository $peranRepository) {}

    public function jalankan(Peran $peran, PeranData $data): Peran
    {
        $peran->fill([
            'Kode' => $data->Kode,
            'Nama' => $data->Nama,
            'Keterangan' => $data->Keterangan,
        ]);

        return $this->peranRepository->simpan($peran);
    }
}
