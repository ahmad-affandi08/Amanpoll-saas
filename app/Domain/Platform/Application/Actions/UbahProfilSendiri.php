<?php

declare(strict_types=1);

namespace App\Domain\Platform\Application\Actions;

use App\Domain\Platform\Domain\Repositories\PenggunaRepository;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class UbahProfilSendiri
{
    public function __construct(private readonly PenggunaRepository $penggunaRepository) {}

    public function jalankan(Pengguna $pengguna, string $nama, string $email, ?string $telepon): Pengguna
    {
        $pengguna->Nama = $nama;
        if ($pengguna->Email !== $email) {
            $pengguna->Email = $email;
            $pengguna->EmailTerverifikasiPada = null;
        }
        $pengguna->Telepon = $telepon;

        return $this->penggunaRepository->simpan($pengguna);
    }
}
