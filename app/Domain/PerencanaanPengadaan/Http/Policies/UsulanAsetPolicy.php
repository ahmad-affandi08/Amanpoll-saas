<?php

declare(strict_types=1);

namespace App\Domain\PerencanaanPengadaan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\PerencanaanPengadaan\Infrastructure\Persistence\Models\UsulanAset;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class UsulanAsetPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function view(Pengguna $pengguna, UsulanAset $usulanAset): bool
    {
        return $this->bolehKelola($pengguna) || $pengguna->Id === $usulanAset->DiajukanOleh;
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->bolehKelola($pengguna);
    }

    public function update(Pengguna $pengguna, UsulanAset $usulanAset): bool
    {
        return $this->bolehKelola($pengguna) || $pengguna->Id === $usulanAset->DiajukanOleh;
    }

    public function delete(Pengguna $pengguna, UsulanAset $usulanAset): bool
    {
        return $this->bolehKelola($pengguna) || $pengguna->Id === $usulanAset->DiajukanOleh;
    }

    private function bolehKelola(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengadaan.Kelola');
    }
}
