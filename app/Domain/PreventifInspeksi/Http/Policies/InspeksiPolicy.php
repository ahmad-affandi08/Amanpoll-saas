<?php

declare(strict_types=1);

namespace App\Domain\PreventifInspeksi\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\PreventifInspeksi\Infrastructure\Persistence\Models\Inspeksi;

final class InspeksiPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, Inspeksi $inspeksi): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }

    public function update(Pengguna $pengguna, Inspeksi $inspeksi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola') || $inspeksi->DilaksanakanOleh === $pengguna->Id;
    }

    public function delete(Pengguna $pengguna, Inspeksi $inspeksi): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pemeliharaan.Kelola');
    }
}
