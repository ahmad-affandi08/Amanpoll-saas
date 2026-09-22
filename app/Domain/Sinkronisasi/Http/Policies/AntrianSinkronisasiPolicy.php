<?php

declare(strict_types=1);

namespace App\Domain\Sinkronisasi\Http\Policies;

use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\Sinkronisasi\Infrastructure\Persistence\Models\AntrianSinkronisasi;

/** Antrean offline hanya boleh dilihat dan diselesaikan oleh pemilik perangkatnya. */
final class AntrianSinkronisasiPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->Status === 'Aktif';
    }

    public function view(Pengguna $pengguna, AntrianSinkronisasi $antrian): bool
    {
        return $this->pemilik($pengguna, $antrian);
    }

    public function update(Pengguna $pengguna, AntrianSinkronisasi $antrian): bool
    {
        return $this->pemilik($pengguna, $antrian);
    }

    private function pemilik(Pengguna $pengguna, AntrianSinkronisasi $antrian): bool
    {
        return $pengguna->Status === 'Aktif'
            && $antrian->perangkatPengguna?->PenggunaId === $pengguna->Id;
    }
}
