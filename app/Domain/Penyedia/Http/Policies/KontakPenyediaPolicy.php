<?php

declare(strict_types=1);

namespace App\Domain\Penyedia\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Penyedia\Infrastructure\Persistence\Models\KontakPenyedia;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class KontakPenyediaPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Penyedia.Kelola');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Penyedia.Kelola');
    }

    public function update(Pengguna $pengguna, KontakPenyedia $kontakPenyedia): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Penyedia.Kelola');
    }

    public function delete(Pengguna $pengguna, KontakPenyedia $kontakPenyedia): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Penyedia.Kelola');
    }
}
