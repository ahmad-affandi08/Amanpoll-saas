<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\PosAnggaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class PosAnggaranPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function create(Pengguna $pengguna): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function update(Pengguna $pengguna, PosAnggaran $posAnggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function delete(Pengguna $pengguna, PosAnggaran $posAnggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function transact(Pengguna $pengguna, PosAnggaran $posAnggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    private function bolehKelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }
}
