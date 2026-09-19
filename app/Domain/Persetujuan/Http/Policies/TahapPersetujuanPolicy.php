<?php

declare(strict_types=1);

namespace App\Domain\Persetujuan\Http\Policies;

use App\Core\Izin\PemeriksaIzin;
use App\Domain\Persetujuan\Infrastructure\Persistence\Models\TahapPersetujuan;
use App\Domain\Platform\Infrastructure\Persistence\Models\Pengguna;

final class TahapPersetujuanPolicy
{
    public function __construct(private readonly PemeriksaIzin $izin) {}

    public function create(Pengguna $pengguna): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }

    public function update(Pengguna $pengguna, TahapPersetujuan $tahapPersetujuan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }

    public function delete(Pengguna $pengguna, TahapPersetujuan $tahapPersetujuan): bool
    {
        return $this->izin->boleh($pengguna->Id, 'Persetujuan.Kelola');
    }
}
