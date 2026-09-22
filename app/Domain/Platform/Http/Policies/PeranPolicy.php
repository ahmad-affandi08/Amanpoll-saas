<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Platform\Infrastructure\Persistence\Models\Peran;

/** Peran dan Izin satu modul IAM, jadi memakai kode izin Pengguna.Kelola yang sama. */
final class PeranPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function view(Pengguna $pengguna, Peran $peran): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function update(Pengguna $pengguna, Peran $peran): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function delete(Pengguna $pengguna, Peran $peran): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }
}
