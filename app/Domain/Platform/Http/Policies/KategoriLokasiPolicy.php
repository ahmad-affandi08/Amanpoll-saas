<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\KategoriLokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KategoriLokasiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function update(Pengguna $pengguna, KategoriLokasi $kategoriLokasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function delete(Pengguna $pengguna, KategoriLokasi $kategoriLokasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
