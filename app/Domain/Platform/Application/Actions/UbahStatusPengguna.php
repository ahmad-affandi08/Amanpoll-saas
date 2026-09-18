<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Shared\Domain\Exceptions\AturanBisnisDilanggar;

final class UbahStatusPengguna
{
    public function __construct(private readonly PenggunaRepository $penggunaRepository) {}

    public function jalankan(Pengguna $pemohon, Pengguna $target, string $status): Pengguna
    {
        if ($pemohon->Id === $target->Id) {
            throw new AturanBisnisDilanggar('Tidak dapat mengubah status akun sendiri.');
        }

        $target->Status = $status;

        return $this->penggunaRepository->simpan($target);
    }
}
