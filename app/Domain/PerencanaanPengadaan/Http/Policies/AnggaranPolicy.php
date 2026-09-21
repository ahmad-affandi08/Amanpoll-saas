<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\Anggaran;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class AnggaranPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function view(Pengguna $pengguna, Anggaran $anggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function update(Pengguna $pengguna, Anggaran $anggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function delete(Pengguna $pengguna, Anggaran $anggaran): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function adjust(Pengguna $pengguna, Anggaran $anggaran): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Anggaran.Sesuaikan');
    }

    private function bolehKelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }
}
