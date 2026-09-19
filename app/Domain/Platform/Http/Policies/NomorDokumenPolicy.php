<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\NomorDokumen;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class NomorDokumenPolicy
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

    public function update(Pengguna $pengguna, NomorDokumen $nomorDokumen): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }

    public function delete(Pengguna $pengguna, NomorDokumen $nomorDokumen): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengaturan.Kelola');
    }
}
