<?php

declare(strict_types=1);

namespace App\Domain\SiklusAset\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;
use App\Domain\SiklusAset\Infrastructure\Persistence\Models\SerahTerimaAset;

final class SerahTerimaAsetPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function viewAny(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function view(Pengguna $pengguna, SerahTerimaAset $serahTerimaAset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }

    public function update(Pengguna $pengguna, SerahTerimaAset $serahTerimaAset): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Aset.Ubah');
    }
}
