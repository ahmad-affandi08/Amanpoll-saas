<?php

declare(strict_types=1);

namespace App\Domain\Platform\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class PenggunaPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function view(Pengguna $pengguna, Pengguna $target): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function update(Pengguna $pengguna, Pengguna $target): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }

    public function ubahStatus(Pengguna $pengguna, Pengguna $target): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Pengguna.Kelola');
    }
}
