<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Lokasi;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class LokasiPolicy
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

    public function update(Pengguna $pengguna, Lokasi $lokasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function delete(Pengguna $pengguna, Lokasi $lokasi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
