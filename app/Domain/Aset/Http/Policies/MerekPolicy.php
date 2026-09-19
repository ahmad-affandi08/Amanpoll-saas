<?php

declare(strict_types=1);

namespace App\Domain\Aset\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Aset\Infrastructure\Persistence\Models\Merek;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class MerekPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Lihat');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Buat');
    }

    public function update(Pengguna $pengguna, Merek $merek): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function delete(Pengguna $pengguna, Merek $merek): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus');
    }
}
