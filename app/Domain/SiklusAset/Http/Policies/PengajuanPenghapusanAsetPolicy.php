<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\PengajuanPenghapusanAset;

final class PengajuanPenghapusanAsetPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus');
    }

    public function view(Pengguna $pengguna, PengajuanPenghapusanAset $pengajuanPenghapusanAset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus');
    }

    public function update(Pengguna $pengguna, PengajuanPenghapusanAset $pengajuanPenghapusanAset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Hapus') || $pengguna->Id === $pengajuanPenghapusanAset->DiajukanOleh;
    }
}
